<?php
namespace App\Services;
use App\Models\AdmissionScoringCriterion;
use App\Models\AdmissionScoringThreshold;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AdmissionLeadScoringService
{
    public function score(array $leadData, array $activities = [])
    {
        $criteria = AdmissionScoringCriterion::where('is_active', true)
            ->where(function ($q) { $q->whereNull('effective_from')->orWhere('effective_from', '<=', Carbon::now()); })
            ->where(function ($q) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', Carbon::now()); })
            ->orderBy('priority', 'asc')->get();

        $totalScore = 0;
        $matchedCriteria = [];
        $unmatchedCriteria = [];

        foreach ($criteria as $criterion) {
            $fieldValue = Arr::get($leadData, $criterion->data_field);
            $isMatch = $this->evaluateCondition($fieldValue, $criterion->operator, $criterion->comparison_value);
            $criterionDetails = [
                'criterion_code' => $criterion->criterion_code,
                'criterion_name' => $criterion->criterion_name,
                'actual_value' => $fieldValue,
                'operator' => $criterion->operator,
                'comparison_value' => $criterion->comparison_value,
                'score' => $criterion->score,
            ];
            if ($isMatch) {
                $totalScore += $criterion->score;
                $matchedCriteria[] = $criterionDetails;
            } else {
                $unmatchedCriteria[] = $criterionDetails;
            }
        }

        $thresholds = AdmissionScoringThreshold::where('is_active', true)->first();
        $hotMin = $thresholds ? $thresholds->hot_lead_min_score : 80;
        $warmMin = $thresholds ? $thresholds->warm_lead_min_score : 50;

        $leadLevel = 'Cold';
        if ($totalScore >= $hotMin) $leadLevel = 'Hot';
        elseif ($totalScore >= $warmMin) $leadLevel = 'Warm';

        return [
            'lead_score' => $totalScore,
            'lead_level' => $leadLevel,
            'matched_criteria' => $matchedCriteria,
            'unmatched_criteria' => $unmatchedCriteria,
            'scored_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function evaluateCondition($actualValue, $operator, $comparisonValue)
    {
        switch ($operator) {
            case 'equal': case '=': return (string) $actualValue === (string) $comparisonValue;
            case 'not_equal': case '!=': return (string) $actualValue !== (string) $comparisonValue;
            case 'greater_than': case '>': return (float) $actualValue > (float) $comparisonValue;
            case 'greater_than_or_equal': case '>=': return (float) $actualValue >= (float) $comparisonValue;
            case 'less_than': case '<': return (float) $actualValue < (float) $comparisonValue;
            case 'less_than_or_equal': case '<=': return (float) $actualValue <= (float) $comparisonValue;
            case 'has_value': return !is_null($actualValue) && $actualValue !== '';
            case 'no_value': return is_null($actualValue) || $actualValue === '';
            case 'contains': return stripos((string) $actualValue, (string) $comparisonValue) !== false;
            case 'in_list':
                $list = array_map('trim', explode(',', (string) $comparisonValue));
                return in_array((string) $actualValue, $list);
            default: return false;
        }
    }

    public function checkAdmissionProbability(array $leadData)
    {
        $major = Arr::get($leadData, 'major');
        $totalScore = Arr::get($leadData, 'scores', 0); // Assuming scores field has total score

        if (!$major || !$totalScore) {
            return [
                'admission_result' => 'Chưa đủ dữ liệu',
                'message' => 'Vui lòng cung cấp ngành học và tổng điểm để hệ thống kiểm tra.',
                'probability_percent' => 0
            ];
        }

        $minScoreRequired = 20; // Giả lập điểm chuẩn là 20
        
        if ($totalScore >= $minScoreRequired) {
            return [
                'admission_result' => 'Khả năng trúng tuyển cao',
                'message' => "Điểm của bạn ($totalScore) đáp ứng đủ điểm chuẩn tham khảo ($minScoreRequired) của ngành $major.",
                'probability_percent' => 95
            ];
        } elseif ($totalScore >= $minScoreRequired - 2) {
            return [
                'admission_result' => 'Cần xem xét thêm',
                'message' => "Điểm của bạn ($totalScore) gần đạt mức điểm chuẩn tham khảo ($minScoreRequired) của ngành $major. Bạn vẫn có cơ hội.",
                'probability_percent' => 60
            ];
        } else {
            return [
                'admission_result' => 'Khả năng trúng tuyển thấp',
                'message' => "Điểm của bạn ($totalScore) thấp hơn khá nhiều so với điểm chuẩn tham khảo ($minScoreRequired).",
                'probability_percent' => 10
            ];
        }
    }
}