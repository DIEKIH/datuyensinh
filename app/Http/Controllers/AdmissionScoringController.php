<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AdmissionScoringCriterion;
use App\Models\AdmissionScoringThreshold;
use App\Services\AdmissionLeadScoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    
    private function validateCriterion(Request $request, $id = null)
    {
        $rules = [
            'criterion_code' => ['required', 'string', 'alpha_dash', Rule::unique('admission_scoring_criteria')->ignore($id)],
            'criterion_name' => 'required|string',
            'category' => 'nullable|string|max:100',
            'data_field' => 'required|string',
            'input_type' => 'nullable|string|in:text,number,select,checkbox,date',
            'show_on_public_form' => 'boolean',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'operator' => 'required|string',
            'comparison_value' => 'nullable|string',
            'score' => 'required|numeric',
            'priority' => 'nullable|integer',
            'is_active' => 'boolean',
        ];

        $messages = [
            'criterion_code.required' => 'Vui lòng nhập mã tiêu chí.',
            'criterion_code.alpha_dash' => 'Mã tiêu chí chỉ được chứa chữ cái không dấu, số, dấu gạch ngang (-) hoặc gạch dưới (_). Không dùng khoảng trắng hay có dấu.',
            'criterion_code.unique' => 'Mã tiêu chí này đã tồn tại, vui lòng chọn mã khác.',
            'criterion_name.required' => 'Vui lòng nhập tên tiêu chí.',
            'data_field.required' => 'Vui lòng nhập trường dữ liệu.',
            'score.required' => 'Vui lòng nhập số điểm cộng.',
            'score.numeric' => 'Điểm cộng phải là số.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        $data = $validator->validate();

        if (!empty($data['show_on_public_form']) && $data['show_on_public_form']) {
            if (strpos($data['data_field'], 'custom.') !== 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data_field' => 'Tiêu chí hiển thị trên form public phải có data_field bắt đầu bằng "custom."'
                ]);
            }
        }

        if (isset($data['input_type']) && $data['input_type'] === 'select') {
            if (empty($data['options']) || !is_array($data['options']) || count($data['options']) < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options' => 'Loại input là select thì options phải có ít nhất 1 phần tử.'
                ]);
            }
        }

        return $request->all();
    }

    public function storeCriterion(Request $request) {
        $data = $this->validateCriterion($request);
        $criterion = AdmissionScoringCriterion::create($data);
        return response()->json(['message' => 'Tạo thành công', 'data' => $criterion]);
    }
    public function updateCriterion(Request $request, $id) {
        $data = $this->validateCriterion($request, $id);
        $criterion = AdmissionScoringCriterion::findOrFail($id);
        $criterion->update($data);
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $criterion]);
    }
    public function deleteCriterion($id) {
        AdmissionScoringCriterion::destroy($id);
        return response()->json(['message' => 'Xóa thành công']);
    }

    public function publicDynamicFields() {
        $fields = AdmissionScoringCriterion::where('show_on_public_form', true)
            ->where('is_active', true)
            ->select('data_field', 'criterion_name', 'input_type', 'options', 'is_required')
            ->orderBy('priority')
            ->get();
        return response()->json($fields);
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