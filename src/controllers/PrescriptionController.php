<?php

class PrescriptionController
{
    public static function create($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $user     = $request->get('user');
        $data     = $request->body();

        if (
            empty($data['patient_id']) ||
            empty($data['doctor_id']) ||
            empty($data['appointment_id']) ||
            empty($data['notes'])
        ) {
            Response::json(null, 422, 'Missing required fieldsss');
            return;
        }

        $prescriptionId = Prescription::create(
            $tenantId,
            $data,
            $user['user_id']
        );

        if (!$prescriptionId) {
            Response::json(null, 500, 'Prescription creation failed');
            return;
        }

        Response::json(
            ['prescription_id' => $prescriptionId],
            201,
            'Prescription created successfully'
        );
    }


    public static function addItem($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $data     = $request->body();
        // Response::json($tenantId);
        // exit;
        if (
            empty($data['prescription_id']) ||
            empty($data['medicine_id']) ||
            empty($data['dosage']) ||
            empty($data['frequency']) ||
            empty($data['duration_days']) ||
            empty($data['quantity'])
        ) {
            Response::json(null, 422, 'Missing required fields');
            return;
        }

        $added = PrescriptionItems::add($tenantId, $data);
        // var_dump($added);
        // var_dump($tenantId);
        // var_dump(Prescription::getById($data['prescription_id'], $tenantId));
        // var_dump(PrescriptionItems::validateMedicine($tenantId, $data['medicine_id']));
        // exit;
        if (!$added) {
            Response::json(null, 500, 'Item addition failed');
            return;
        }

        Response::json(null, 201, 'Item added successfully');
    }


    public static function getById($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');

        $prescription = Prescription::getById($id, $tenantId);

        if (!$prescription) {
            Response::json(null, 404, 'Prescription not found');
            return;
        }

        $items = PrescriptionItems::getByPrescription($tenantId, $id);

        $prescription['items'] = $items;

        Response::json(
            $prescription,
            200,
            'Prescription fetched successfully'
        );
    }

    public static function getAll($request, $response)
    {
        $tenantId = $request->get('tenant_id');

        $prescriptions = Prescription::getAll($tenantId);

        Response::json(
            $prescriptions,
            200,
            'Prescriptions fetched successfully'
        );
    }
}