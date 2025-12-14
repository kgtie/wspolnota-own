<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailingMail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mailing_list_id', 
        'email', 
        'confirmation_token', 
        'confirmed_at',
        'unsubscribe_token'
    ];

    // Helper: czy potwierdzony?
    public function isConfirmed() {
        return $this->confirmed_at !== null;
    }
}