<?php

namespace Tests\Unit;

use App\Services\ChatbotQueryAnalyzerService;
use Tests\TestCase;

class ChatbotQueryAnalyzerServiceTest extends TestCase
{
    private $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = app(ChatbotQueryAnalyzerService::class);
    }

    public function test_food_location_is_not_dormitory()
    {
        $analysis = $this->analyzer->analyze('Mua đồ ăn ở đâu?');

        $this->assertContains('food_location', $analysis['intents']);
        $this->assertNotContains('dormitory', $analysis['intents']);
        $this->assertNotContains('meal_support', $analysis['intents']);
    }

    public function test_boarding_support_contains_two_real_intents()
    {
        $analysis = $this->analyzer->analyze('Có bao ăn ở không?');

        $this->assertContains('dormitory', $analysis['intents']);
        $this->assertContains('meal_support', $analysis['intents']);
        $this->assertTrue($analysis['is_multi_intent']);
        $this->assertFalse($analysis['allow_approximate_match']);
    }

    public function test_short_ambiguous_question_requires_clarification()
    {
        $analysis = $this->analyzer->analyze('ăn');
        $response = $this->analyzer->localResponse($analysis);

        $this->assertTrue($analysis['is_short']);
        $this->assertNotNull($response);
        $this->assertStringContainsString('căn tin', $response);
    }

    public function test_personal_application_status_never_uses_library()
    {
        $analysis = $this->analyzer->analyze(
            'Hồ sơ của tôi đã được duyệt chưa?'
        );

        $this->assertTrue($analysis['is_personal']);
        $this->assertFalse($analysis['allow_approximate_match']);
        $this->assertNotNull($this->analyzer->localResponse($analysis));
    }

    public function test_major_is_entity_not_second_intent_for_tuition()
    {
        $analysis = $this->analyzer->analyze(
            'Học phí ngành CNTT năm 2026 là bao nhiêu?'
        );

        $this->assertSame(['tuition'], $analysis['intents']);
        $this->assertContains(
            'cong_nghe_thong_tin',
            $analysis['entities']['majors']
        );
        $this->assertSame(2026, $analysis['resolved_year']);
    }

    public function test_negation_and_comparison_require_exact_match()
    {
        $negation = $this->analyzer->analyze(
            'Trường không có ký túc xá phải không?'
        );
        $comparison = $this->analyzer->analyze(
            'Ký túc xá rẻ hơn trường khác không?'
        );

        $this->assertTrue($negation['is_negation']);
        $this->assertFalse($negation['allow_approximate_match']);
        $this->assertTrue($comparison['is_comparison']);
        $this->assertFalse($comparison['allow_approximate_match']);
    }

    public function test_gibberish_is_rejected()
    {
        $analysis = $this->analyzer->analyze('sdfuyas8dow9e7yr');

        $this->assertTrue($analysis['is_gibberish']);
        $this->assertFalse($analysis['allow_approximate_match']);
    }

    public function test_time_sensitive_library_answer_requires_year()
    {
        $this->assertFalse($this->analyzer->isEligibleForLibrary(
            'Học phí ngành Công nghệ thông tin là bao nhiêu?',
            'Học phí được thu theo quy định hiện hành của nhà trường.'
        ));

        $this->assertTrue($this->analyzer->isEligibleForLibrary(
            'Học phí ngành Công nghệ thông tin là bao nhiêu?',
            'Mức học phí áp dụng cho năm tuyển sinh 2026 được công bố theo đề án của trường.'
        ));
    }
}
