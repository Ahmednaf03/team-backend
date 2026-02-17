<?php



class PatientController{

    public static function get($request, $response){
        // this will be provided by tenant middleware
        $tenantId = $request->get('tenant_id');
        $patients = Patient::getAll($tenantId);

        Response::json($patients);
    }
}