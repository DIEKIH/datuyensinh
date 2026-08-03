<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdmissionScoringCriterion extends Model
{
    protected $table = 'admission_scoring_criteria';
    protected $fillable = ['category', 'criterion_code', 'criterion_name', 'description', 'data_field', 'operator', 'comparison_value', 'score', 'priority', 'is_active', 'effective_from', 'effective_to', 'input_type', 'options', 'show_on_public_form', 'is_required'];
    protected $casts = ['is_active' => 'boolean', 'effective_from' => 'datetime', 'effective_to' => 'datetime', 'options' => 'array', 'show_on_public_form' => 'boolean', 'is_required' => 'boolean'];
}