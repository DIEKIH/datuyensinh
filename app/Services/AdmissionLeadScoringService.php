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
        // Decode profile if it is a string (e.g. from DB::table)
        if (isset($leadData['profile']) && is_string($leadData['profile'])) {
            $leadData['profile'] = json_decode($leadData['profile'], true) ?? [];
        }

        // Tính toán các chỉ số tương tác (Activities)
        $activityStats = [
            'total_inbound' => 0,
            'chat_count' => 0,
            'comment_count' => 0,
            'like_count' => 0,
            'share_count' => 0,
            'has_facebook' => false,
            'has_zalo' => false,
            'has_website' => false,
        ];

        foreach ($activities as $act) {
            $type = strtolower($act['type'] ?? '');
            $channel = strtolower($act['channel'] ?? '');
            $direction = strtolower($act['direction'] ?? 'inbound');

            // Tính điểm tương tác chủ động của khách hàng
            if ($direction === 'inbound') {
                $activityStats['total_inbound']++;
                if ($type === 'chat' || $type === 'message') $activityStats['chat_count']++;
                if ($type === 'comment') $activityStats['comment_count']++;
                if ($type === 'like' || $type === 'reaction') $activityStats['like_count']++;
                if ($type === 'share') $activityStats['share_count']++;

                if ($channel === 'facebook' || $channel === 'messenger') $activityStats['has_facebook'] = true;
                if ($channel === 'zalo') $activityStats['has_zalo'] = true;
                if ($channel === 'website' || $channel === 'web') $activityStats['has_website'] = true;
            }
        }
        $activityStats['channel_count'] = collect([
            $activityStats['has_facebook'],
            $activityStats['has_zalo'],
            $activityStats['has_website'],
        ])->filter()->count();

        $leadData['activity'] = $activityStats;

        $criteria = AdmissionScoringCriterion::where('is_active', true)
            ->where(function ($q) { $q->whereNull('effective_from')->orWhere('effective_from', '<=', Carbon::now()); })
            ->where(function ($q) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', Carbon::now()); })
            ->orderBy('priority', 'asc')->get();

        $weightedScore = 0;
        $maxPossible = 0;
        $matchedCriteria = [];
        $unmatchedCriteria = [];

        foreach ($criteria as $criterion) {
            $weight = max(0, (float) $criterion->score);
            $maxPossible += $weight;

            $fieldValue = Arr::get($leadData, $criterion->data_field);
            $isMatch = $this->evaluateCondition($fieldValue, $criterion->operator, $criterion->comparison_value);

            $criterionDetails = [
                'criterion_id' => $criterion->id,
                'criterion_code' => $criterion->criterion_code,
                'criterion_name' => $criterion->criterion_name,
                'actual_value' => $fieldValue,
                'weight' => $weight,
            ];

            if ($isMatch) {
                $weightedScore += $weight;
                $matchedCriteria[] = $criterionDetails;
            } else {
                $unmatchedCriteria[] = $criterionDetails;
            }
        }

        $normalizedScore = $maxPossible > 0
            ? round(($weightedScore / $maxPossible) * 100, 2)
            : 0;

        $thresholds = AdmissionScoringThreshold::where('is_active', true)->first();
        $hotMin = $thresholds ? $thresholds->hot_lead_min_score : 80;
        $warmMin = $thresholds ? $thresholds->warm_lead_min_score : 50;

        $leadLevel = 'Cold';
        if ($normalizedScore >= $hotMin) $leadLevel = 'Hot';
        elseif ($normalizedScore >= $warmMin) $leadLevel = 'Warm';

        return [
            'lead_score' => $normalizedScore,
            'raw_weighted_score' => $weightedScore,
            'max_possible_score' => $maxPossible,
            'lead_level' => $leadLevel,
            'matched_criteria' => $matchedCriteria,
            'unmatched_criteria' => $unmatchedCriteria,
            'scored_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function evaluateCondition($actualValue, $operator, $comparisonValue)
    {
        // Xử lý trim và chữ thường cho so sánh chuỗi
        $actual = is_string($actualValue) ? mb_strtolower(trim($actualValue)) : $actualValue;
        $comp = is_string($comparisonValue) ? mb_strtolower(trim($comparisonValue)) : $comparisonValue;

        if (is_array($actualValue)) {
            $actualArray = array_map(function($v) {
                return is_string($v) ? mb_strtolower(trim($v)) : $v;
            }, $actualValue);
            
            if ($operator === 'contains' || $operator === '=') {
                return in_array($comp, $actualArray);
            }
            if ($operator === 'has_value') {
                return count($actualArray) > 0;
            }
            return false;
        }

        switch ($operator) {
            case '=':
                return (string) $actual === (string) $comp;
            case '>':
                return (float) $actualValue > (float) $comparisonValue;
            case '>=':
                return (float) $actualValue >= (float) $comparisonValue;
            case '<':
                return (float) $actualValue < (float) $comparisonValue;
            case '<=':
                return (float) $actualValue <= (float) $comparisonValue;
            case 'contains':
                return strpos((string) $actual, (string) $comp) !== false;
            case 'has_value':
                return !empty($actualValue);
            default:
                return false;
        }
    }

    public function extractAndMergeCustomFields(array $customInput)
    {
        $customInputs = [];
        $dynamicCriteria = AdmissionScoringCriterion::where('show_on_public_form', true)
            ->where('is_active', true)->get();
            
        foreach ($dynamicCriteria as $criterion) {
            $key = str_replace('custom.', '', $criterion->data_field);
            if (isset($customInput[$key])) {
                $val = $customInput[$key];
                if ($criterion->input_type === 'select') {
                    $options = collect($criterion->options)->pluck('value')->toArray();
                    if (!in_array((string)$val, $options)) continue;
                }
                $customInputs[$key] = $val;
            }
        }
        return $customInputs;
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