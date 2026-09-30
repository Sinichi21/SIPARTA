<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterheadProfile extends Model {
    protected $fillable = [
        'name','organization_name','parent_organization','address','phone','email','website','city',
        'logo_path','logo_original_name','logo_secondary_path','logo_secondary_original_name',
        'signatory_name','signatory_nip','signatory_position','is_default','is_active','created_by','updated_by',
    ];
    protected function casts(): array { return ['is_default'=>'boolean','is_active'=>'boolean']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class,'updated_by'); }
    public function templates(): HasMany { return $this->hasMany(LetterTemplate::class); }
}
