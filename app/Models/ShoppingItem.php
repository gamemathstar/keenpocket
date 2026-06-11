<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingItem extends Model
{
    use HasFactory;

    protected $table = 'pocket_shopping_items';

    protected $fillable = ['pocket_id', 'name', 'unit_price', 'person_count', 'category'];
}
