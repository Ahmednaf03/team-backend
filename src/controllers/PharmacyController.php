<?php

class PharmacyController
{
    public static function verify($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $user     = $request->get('user');

        $prescription = Prescription::getById($id, $tenantId);

        if (!$prescription) {
            Response::json(null, 404, 'Prescription not found');
            return;
        }

        if ($prescription['status'] !== 'PENDING') {
            Response::json(null, 422, 'Only PENDING prescriptions can be verified');
            return;
        }

        $updated = Prescription::updateStatus(
            $tenantId,
            $id,
            'VERIFIED',
            $user['user_id']
        );

        if (!$updated) {
            Response::json(null, 500, 'Verification failed');
            return;
        }

        Response::json($updated, 200, 'Prescription verified successfully');
    }


    public static function dispense($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $user     = $request->get('user');

        $prescription = Prescription::getById($id, $tenantId);

        if (!$prescription) {
            Response::json(null, 404, 'Prescription not found');
            return;
        }

        if ($prescription['status'] !== 'VERIFIED') {
            Response::json(null, 422, 'Prescription must be VERIFIED first');
            return;
        }

        $items = PrescriptionItems::getByPrescription($tenantId, $id);

        foreach ($items as $item) {
            Medicine::reduceStock(
                $tenantId,
                $item['medicine_id'],
                $item['quantity']
            );
        }

        $updated = Prescription::updateStatus(
            $tenantId,
            $id,
            'DISPENSED',
            $user['user_id']
        );

        if (!$updated) {
            Response::json(null, 500, 'Dispense failed');
            return;
        }

        Response::json($updated, 200, 'Prescription dispensed successfully');
    }
}