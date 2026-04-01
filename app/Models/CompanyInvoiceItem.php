<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInvoiceItem extends Model
{
	use HasFactory;

	protected $table = 'company_invoice_items';

	protected $fillable = [
		'company_invoice_id',
		'item_type',
		'description',
		'quantity',
		'unit_price',
		'amount',
		'meta_json',
	];

	protected $casts = [
		'quantity' => 'integer',
		'unit_price' => 'decimal:2',
		'amount' => 'decimal:2', // Database column is now decimal(15,2) to support large values
		'meta_json' => 'array',
	];

	public function companyInvoice(): BelongsTo
	{
		return $this->belongsTo(CompanyInvoice::class);
	}
}


