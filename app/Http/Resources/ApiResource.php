<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ApiResource extends JsonResource {
    public function toArray(Request $request): array { return parent::toArray($request); }
    public function with(Request $request): array { return ['success'=>true,'message'=>'Request completed successfully.']; }
}
