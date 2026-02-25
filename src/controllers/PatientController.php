<?php



class PatientController{

    public static function get($request, $response){
        // this will be provided by tenant middleware
        $tenantId = $request->get('tenant_id');
        $patients = Patient::getAll($tenantId);

        Response::json($patients);
    }

public static function getById($request, $response, $id) {

    $tenantId = $request->get('tenant_id');

    $patient = Patient::getById($id, $tenantId);

    if (!$patient) {
        Response::json(null, 404, 'Patient not found');
        return;
    }

    Response::json($patient, 200, 'Patient fetched successfully');
}



        public static function create($request, $response){
        // this will be provided by tenant middleware
        $tenantId = $request->get('tenant_id');
        // request body is being captured here so no need for sending manually in the controller
        $patients = Patient::create($tenantId, $request->Body());

        if ($patients) {
            Response::json($patients, 201, 'Patient created successfully');
            exit;
        }
        Response::json($patients, 500, 'Patient creation failed');
    }


        public static function update($request, $response, $id){

             $tenantId = $request->get('tenant_id');

             $updated = Patient::update($tenantId, $id, $request->body());

            if (!$updated) {
                Response::json(null, 400, 'Update failed or conflict');
                return;
            }
            Response::json($updated, 200, 'Patient updated successfully');
}


        public static function delete($request, $response, $id){
        // this will be provided by tenant middleware
        $tenantId = $request->get('tenant_id');
        // $userId = $request->get('user_id');
        $patients = Patient::softDelete($tenantId,$id); //request body deleted here if i break add that

        Response::json($patients, 200, 'Patient deleted successfully');
    }
}