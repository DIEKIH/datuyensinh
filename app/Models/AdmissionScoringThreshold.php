<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdmissionScoringThreshold extends Model
{
    protected $table = 'admission_scoring_thresholds';
    protected $fillable = ['hot_lead_min_score', 'warm_lead_min_score', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}