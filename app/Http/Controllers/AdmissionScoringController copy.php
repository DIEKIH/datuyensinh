<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AdmissionScoringCriterion;
use App\Models\AdmissionScoringThreshold;
use App\Services\AdmissionLeadScoringService;
use Illuminate\Support\Facades\DB;

class AdmissionScoringController extends Controller
{
    protected $scoringService;

    public function __construct(AdmissionLeadScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    public function getSettings() { 
        return response()->json(['ai_comment_reply_enabled' => env('AI_COMMENT_REPLY_ENABLED', true)]); 
    }

    public function getCriteria() { return response()->json(AdmissionScoringCriterion::orderBy('priority')->get()); }
    public function storeCriterion(Request $request) {
        $criterion = AdmissionScoringCriterion::create($request->all());
        return response()->json(['message' => 'Tạo thành công', 'data' => $criterion]);
    }
    public function updateCriterion(Request $request, $id) {
        $criterion = AdmissionScoringCriterion::findOrFail($id);
        $criterion->update($request->all());
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $criterion]);
    }
    public function deleteCriterion($id) {
        AdmissionScoringCriterion::destroy($id);
        return response()->json(['message' => 'Xóa thành công']);
    }

    public function scoreLead(Request $request) {
        $result = $this->scoringService->scoreLead($request->all());
        return response()->json($result);
    }

    public function checkAdmission(Request $request) {
        $result = $this->scoringService->checkAdmissionProbability($request->all());
        $result['ai_comment_reply_enabled'] = env('AI_COMMENT_REPLY_ENABLED', true);
        return response()->json($result);
    }
}