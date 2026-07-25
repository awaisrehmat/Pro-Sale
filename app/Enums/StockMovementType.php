<?php
namespace App\Enums;
enum StockMovementType:string { case OpeningStock='opening_stock'; case Purchase='purchase'; case PurchaseCancellation='purchase_cancellation'; case Sale='sale'; case SaleCancellation='sale_cancellation'; case PositiveAdjustment='positive_adjustment'; case NegativeAdjustment='negative_adjustment'; }
