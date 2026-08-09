<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasurement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitOfMeasurementController extends Controller {
    public function index(){return $this->ok(UnitOfMeasurement::withCount('products')->orderBy('name')->get());}
    public function store(Request $r){$uom=UnitOfMeasurement::create($r->validate($this->rules()));return $this->ok($uom,'Unit of measurement created.',201);}
    public function update(Request $r,UnitOfMeasurement $unit){$unit->update($r->validate($this->rules($unit)));return $this->ok($unit,'Unit of measurement updated.');}
    public function destroy(UnitOfMeasurement $unit){if($unit->products()->exists())throw ValidationException::withMessages(['unit'=>'This unit is assigned to products and cannot be deleted.']);$unit->delete();return $this->ok(null,'Unit of measurement deleted.');}
    private function rules(?UnitOfMeasurement $unit=null):array{return ['name'=>['required','string','max:80',Rule::unique('units_of_measurement')->ignore($unit)],'symbol'=>['required','string','max:20',Rule::unique('units_of_measurement')->ignore($unit)],'decimal_places'=>'required|integer|min:0|max:3','is_active'=>'boolean'];}
    private function ok($data,$message='Units retrieved.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
