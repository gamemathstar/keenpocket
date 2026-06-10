<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $table = "invoice_item";
    protected $fillable = [
        'invoice_id', 'item_id', 'amount', 'type', 'month',
    ];
}
