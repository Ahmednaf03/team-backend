<?php

class BillingController {

    public static function generate($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $prescription = Invoice::getValidPrescription($id, $tenantId);

        if (!$prescription) {
            Response::json(null, 404, 'Prescription not found or not DISPENSED');
            return;
        }

        if (Invoice::existsForPrescription($id, $tenantId)) {
            Response::json(null, 422, 'Invoice already generated');
            return;
        }

        if (!InvoiceItem::hasItems($id, $tenantId)) {
            Response::json(null, 422, 'No prescription items found');
            return;
        }

        $invoiceId = Invoice::create($tenantId, $id, $prescription['patient_id']);

        if (!$invoiceId) {
            Response::json(null, 400, 'Invoice creation failed');
            return;
        }

        $total = InvoiceItem::createFromPrescription($invoiceId, $id, $tenantId);

        Invoice::updateTotal($invoiceId, $tenantId, $total);

        Response::json(
            [
                'invoice_id' => $invoiceId,
                'total'      => $total
            ],
            201,
            'Invoice generated successfully'
        );
    }

    public static function pay($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $invoice = Invoice::getById($id, $tenantId);

        if (!$invoice) {
            Response::json(null, 404, 'Invoice not found');
            return;
        }

        if ($invoice['status'] === 'PAID') {
            Response::json(null, 422, 'Invoice already paid');
            return;
        }

        $paid = Invoice::markPaid($id, $tenantId);

        if (!$paid) {
            Response::json(null, 400, 'Payment failed');
            return;
        }

        Response::json($paid, 200, 'Payment successful');
    }

    public static function summary($request, $response) {

        $tenantId = $request->get('tenant_id');

        $data = Invoice::getSummaryByTenant($tenantId);

        Response::json($data, 200, 'Invoice summary fetched successfully');
    }
}