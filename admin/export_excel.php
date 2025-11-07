<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // You'll need to install PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

// Get date range
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Create new spreadsheet
$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('Graduation Pictorial System')
    ->setLastModifiedBy('Admin')
    ->setTitle('Graduation Pictorial Report')
    ->setSubject('Graduation Pictorial Statistics')
    ->setDescription('Report generated from Graduation Pictorial System');

// Create the first sheet - Overview
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Overview');

// Add title
$sheet->setCellValue('A1', 'Graduation Pictorial System - Report');
$sheet->setCellValue('A2', "Period: $start_date to $end_date");
$sheet->mergeCells('A1:E1');
$sheet->mergeCells('A2:E2');

// Style the header
$sheet->getStyle('A1:E2')->getFont()->setBold(true);
$sheet->getStyle('A1:E2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Get appointment statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
    FROM appointments
    WHERE created_at BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$appointment_stats = $stmt->get_result()->fetch_assoc();

// Add appointment statistics
$sheet->setCellValue('A4', 'Appointment Statistics');
$sheet->getStyle('A4')->getFont()->setBold(true);

$sheet->setCellValue('A5', 'Total Appointments');
$sheet->setCellValue('B5', $appointment_stats['total_appointments']);

$sheet->setCellValue('A6', 'Pending Appointments');
$sheet->setCellValue('B6', $appointment_stats['pending_appointments']);

$sheet->setCellValue('A7', 'Approved Appointments');
$sheet->setCellValue('B7', $appointment_stats['approved_appointments']);

$sheet->setCellValue('A8', 'Completed Appointments');
$sheet->setCellValue('B8', $appointment_stats['completed_appointments']);

$sheet->setCellValue('A9', 'Cancelled Appointments');
$sheet->setCellValue('B9', $appointment_stats['cancelled_appointments']);

// Get payment statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END) as verified_amount,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_payments,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_payments
    FROM payments
    WHERE uploaded_at BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$payment_stats = $stmt->get_result()->fetch_assoc();

// Add payment statistics
$sheet->setCellValue('A11', 'Payment Statistics');
$sheet->getStyle('A11')->getFont()->setBold(true);

$sheet->setCellValue('A12', 'Total Payments');
$sheet->setCellValue('B12', $payment_stats['total_payments']);

$sheet->setCellValue('A13', 'Total Amount');
$sheet->setCellValue('B13', $payment_stats['total_amount']);
$sheet->getStyle('B13')->getNumberFormat()->setFormatCode('₱#,##0.00');

$sheet->setCellValue('A14', 'Verified Payments');
$sheet->setCellValue('B14', $payment_stats['verified_payments']);

$sheet->setCellValue('A15', 'Verified Amount');
$sheet->setCellValue('B15', $payment_stats['verified_amount']);
$sheet->getStyle('B15')->getNumberFormat()->setFormatCode('₱#,##0.00');

$sheet->setCellValue('A16', 'Pending Payments');
$sheet->setCellValue('B16', $payment_stats['pending_payments']);

$sheet->setCellValue('A17', 'Rejected Payments');
$sheet->setCellValue('B17', $payment_stats['rejected_payments']);

// Auto-size columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create second sheet - Appointments
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Appointments');

// Add appointments header
$sheet->setCellValue('A1', 'Student');
$sheet->setCellValue('B1', 'Preferred Date');
$sheet->setCellValue('C1', 'Preferred Time');
$sheet->setCellValue('D1', 'Status');
$sheet->setCellValue('E1', 'Created At');

$sheet->getStyle('A1:E1')->getFont()->setBold(true);
$sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setRGB('CCCCCC');

// Get appointments data
$stmt = $conn->prepare("
    SELECT a.*, u.full_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.created_at BETWEEN ? AND ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Add appointments data
$row = 2;
foreach ($appointments as $apt) {
    $sheet->setCellValue('A' . $row, $apt['full_name']);
    $sheet->setCellValue('B' . $row, date('Y-m-d', strtotime($apt['preferred_date'])));
    $sheet->setCellValue('C' . $row, date('H:i', strtotime($apt['preferred_time'])));
    $sheet->setCellValue('D' . $row, ucfirst($apt['status']));
    $sheet->setCellValue('E' . $row, date('Y-m-d H:i:s', strtotime($apt['created_at'])));
    $row++;
}

// Auto-size columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create third sheet - Payments
$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Payments');

// Add payments header
$sheet->setCellValue('A1', 'Student');
$sheet->setCellValue('B1', 'Amount');
$sheet->setCellValue('C1', 'Status');
$sheet->setCellValue('D1', 'Upload Date');

$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setRGB('CCCCCC');

// Get payments data
$stmt = $conn->prepare("
    SELECT p.*, u.full_name
    FROM payments p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN users u ON a.student_id = u.id
    WHERE p.uploaded_at BETWEEN ? AND ?
    ORDER BY p.uploaded_at DESC
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Add payments data
$row = 2;
foreach ($payments as $payment) {
    $sheet->setCellValue('A' . $row, $payment['full_name']);
    $sheet->setCellValue('B' . $row, $payment['amount']);
    $sheet->setCellValue('C' . $row, ucfirst($payment['status']));
    $sheet->setCellValue('D' . $row, date('Y-m-d H:i:s', strtotime($payment['uploaded_at'])));
    $row++;
}

// Format amount column
$sheet->getStyle('B2:B' . ($row-1))->getNumberFormat()->setFormatCode('₱#,##0.00');

// Auto-size columns
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set the first sheet as active
$spreadsheet->setActiveSheetIndex(0);

// Create writer and output file
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="graduation_pictorial_report.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
?>
