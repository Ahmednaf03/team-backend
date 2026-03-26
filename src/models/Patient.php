<?php
require_once __DIR__ . '/../helpers/Encryption.php';

class Patient
{
    private static function db($tenantId)
    {
        return DatabaseManager::tenant($tenantId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET ALL
    // ─────────────────────────────────────────────────────────────────────────
    public static function getAll($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, age, gender, phone, address, diagnosis, email, status, created_at
            FROM patients
            WHERE status = 'active'
            AND deleted_at IS NULL
        ");

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['name']      = Encryption::decrypt($row['name']);
            $row['age']       = (int) Encryption::decrypt($row['age']);
            $row['gender']    = Encryption::decrypt($row['gender']);
            $row['phone']     = Encryption::decrypt($row['phone']);
            $row['address']   = Encryption::decrypt($row['address']);
            $row['diagnosis'] = Encryption::decrypt($row['diagnosis']);
            $row['email']     = $row['email'] ? Encryption::decrypt($row['email']) : null;
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET BY ID
    // ─────────────────────────────────────────────────────────────────────────
    public static function getById($id, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, age, gender, phone, address, diagnosis, email, status, created_at
            FROM patients
            WHERE id = ?
            AND status = 'active'
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['name']      = Encryption::decrypt($row['name']);
        $row['age']       = (int) Encryption::decrypt($row['age']);
        $row['gender']    = Encryption::decrypt($row['gender']);
        $row['phone']     = Encryption::decrypt($row['phone']);
        $row['address']   = Encryption::decrypt($row['address']);
        $row['diagnosis'] = Encryption::decrypt($row['diagnosis']);
        $row['email']     = $row['email'] ? Encryption::decrypt($row['email']) : null;

        return $row;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET FULL PROFILE  (called by the patient after login — their own data only)
    // Returns: core info + appointments + prescriptions (with items) + invoices
    // ─────────────────────────────────────────────────────────────────────────
    public static function getProfile($patientId, $tenantId)
    {
        $db = self::db($tenantId);

        // 1. Core patient record
        $stmt = $db->prepare("
            SELECT id, name, age, gender, phone, address, diagnosis, email, status, created_at
            FROM patients
            WHERE id = ?
            AND status = 'active'
            AND deleted_at IS NULL
        ");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) return null;

        $patient['name']      = Encryption::decrypt($patient['name']);
        $patient['age']       = (int) Encryption::decrypt($patient['age']);
        $patient['gender']    = Encryption::decrypt($patient['gender']);
        $patient['phone']     = Encryption::decrypt($patient['phone']);
        $patient['address']   = Encryption::decrypt($patient['address']);
        $patient['diagnosis'] = Encryption::decrypt($patient['diagnosis']);
        $patient['email']     = $patient['email'] ? Encryption::decrypt($patient['email']) : null;

        // 2. Appointments — joined with doctor name
$stmt = $db->prepare("
    SELECT
        a.id,
        a.scheduled_at,
        a.status,
        a.notes,
        a.created_at,
        u.name AS doctor_name
    FROM appointments a
    LEFT JOIN users u ON u.id = a.doctor_id AND u.deleted_at IS NULL
    WHERE a.patient_id = ?
    AND a.deleted_at IS NULL
    ORDER BY a.scheduled_at DESC
");
$stmt->execute([$patientId]);

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($appointments as &$appt) {
    if (!empty($appt['doctor_name'])) {
        $appt['doctor_name'] = Encryption::decrypt($appt['doctor_name']);
    }
}

$patient['appointments'] = $appointments;

        // 3. Prescriptions — joined with doctor name + their items
        $stmt = $db->prepare("
    SELECT
        p.id,
        p.appointment_id,
        p.notes,
        p.status,
        p.prescription_date,
        p.dispensed_at,
        u.name AS doctor_name
    FROM prescriptions p
    LEFT JOIN users u ON u.id = p.doctor_id AND u.deleted_at IS NULL
    WHERE p.patient_id = ?
    AND p.deleted_at IS NULL
    ORDER BY p.prescription_date DESC
");
$stmt->execute([$patientId]);

$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($prescriptions as &$rx) {
    // decrypt doctor name
    if (!empty($rx['doctor_name'])) {
        $rx['doctor_name'] = Encryption::decrypt($rx['doctor_name']);
    }

    // existing decrypt
    $rx['notes'] = $rx['notes'] ? Encryption::decrypt($rx['notes']) : null;

    $itemStmt = $db->prepare("
        SELECT
            pi.id,
            pi.quantity,
            pi.frequency,
            pi.duration_days,
            pi.dosage,
            pi.instructions,
            m.name AS medicine_name
        FROM prescription_items pi
        JOIN medicines m ON m.id = pi.medicine_id AND m.deleted_at IS NULL
        WHERE pi.prescription_id = ?
        AND pi.deleted_at IS NULL
    ");
    $itemStmt->execute([$rx['id']]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['dosage']       = Encryption::decrypt($item['dosage']);
        $item['instructions'] = Encryption::decrypt($item['instructions']);
    }

    $rx['items'] = $items;
}

$patient['prescriptions'] = $prescriptions;

        // 4. Invoices
        $stmt = $db->prepare("
            SELECT
                i.id,
                i.prescription_id,
                i.total_amount,
                i.status,
                i.created_at,
                i.paid_at
            FROM invoices i
            WHERE i.patient_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$patientId]);
        $patient['invoices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $patient;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────────
    public static function create($tenantId, $data)
    {
        if (empty($data['email']) || empty($data['password'])) {
            return null;
        }

        $emailEncrypted = Encryption::encrypt(strtolower(trim($data['email'])));
        $emailHash      = Encryption::blindIndex($data['email']);
        $passwordHash   = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = self::db($tenantId)->prepare("
            INSERT INTO patients
                (name, age, gender, phone, address, diagnosis, email, email_hash, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            Encryption::encrypt($data['name']),
            Encryption::encrypt((string) $data['age']),
            Encryption::encrypt($data['gender']),
            Encryption::encrypt($data['phone']),
            Encryption::encrypt($data['address']   ?? ''),
            Encryption::encrypt($data['diagnosis'] ?? ''),
            $emailEncrypted,
            $emailHash,
            $passwordHash,
        ]);

        return self::db($tenantId)->lastInsertId();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE  (email + password excluded — dedicated endpoints for those)
    // ─────────────────────────────────────────────────────────────────────────
    public static function update($tenantId, $id, $data)
    {
        $allowed = ['name', 'age', 'gender', 'phone', 'address', 'diagnosis'];
        $fields  = [];
        $values  = [];

        foreach ($allowed as $column) {
            if (!isset($data[$column])) continue;
            $fields[] = "$column = ?";
            $values[] = ($column === 'age')
                ? Encryption::encrypt((string) $data[$column])
                : Encryption::encrypt($data[$column]);
        }

        if (empty($fields)) return false;

        $sql      = "UPDATE patients SET " . implode(', ', $fields) . "
                     WHERE id = ? AND deleted_at IS NULL";
        $values[] = $id;

        $stmt = self::db($tenantId)->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────────
    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE patients SET deleted_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public static function forceDelete($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("DELETE FROM patients WHERE id = ?");
        return $stmt->execute([$id]);
    }
}