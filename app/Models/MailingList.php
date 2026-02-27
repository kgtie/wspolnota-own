<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailingList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function mails(): HasMany
    {
        return $this->hasMany(MailingMail::class, 'mailing_list_id');
    }
}
