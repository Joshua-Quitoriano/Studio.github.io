<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // You'll need to install TCPDF via Composer

use TCPDF;

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

// Get date range
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Graduation Pictorial System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Graduation Pictorial Report');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Graduation Pictorial System - Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, "Period: $start_date to $end_date", 0, 1, 'C');
$pdf->Ln(10);

// Appointment Statistics
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

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Appointment Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(100, 8, 'Total Appointments:', 0, 0);
$pdf->Cell(0, 8, $appointment_stats['total_appointments'], 0, 1);

$pdf->Cell(100, 8, 'Pending Appointments:', 0, 0);
$pdf->Cell(0, 8, $appointment_stats['pending_appointments'], 0, 1);

$pdf->Cell(100, 8, 'Approved Appointments:', 0, 0);
$pdf->Cell(0, 8, $appointment_stats['approved_appointments'], 0, 1);

$pdf->Cell(100, 8, 'Completed Appointments:', 0, 0);
$pdf->Cell(0, 8, $appointment_stats['completed_appointments'], 0, 1);

$pdf->Cell(100, 8, 'Cancelled Appointments:', 0, 0);
$pdf->Cell(0, 8, $appointment_stats['cancelled_appointments'], 0, 1);

$pdf->Ln(10);

// Payment Statistics
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

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Payment Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(100, 8, 'Total Payments:', 0, 0);
$pdf->Cell(0, 8, $payment_stats['total_payments'], 0, 1);

$pdf->Cell(100, 8, 'Total Amount:', 0, 0);
$pdf->Cell(0, 8, '₱' . number_format($payment_stats['total_amount'], 2), 0, 1);

$pdf->Cell(100, 8, 'Verified Payments:', 0, 0);
$pdf->Cell(0, 8, $payment_stats['verified_payments'], 0, 1);

$pdf->Cell(100, 8, 'Verified Amount:', 0, 0);
$pdf->Cell(0, 8, '₱' . number_format($payment_stats['verified_amount'], 2), 0, 1);

$pdf->Cell(100, 8, 'Pending Payments:', 0, 0);
$pdf->Cell(0, 8, $payment_stats['pending_payments'], 0, 1);

$pdf->Cell(100, 8, 'Rejected Payments:', 0, 0);
$pdf->Cell(0, 8, $payment_stats['rejected_payments'], 0, 1);

$pdf->Ln(10);

// Recent Appointments
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Recent Appointments', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$stmt = $conn->prepare("
    SELECT a.*, u.full_name
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.created_at BETWEEN ? AND ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$recent_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Table header
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(60, 8, 'Student', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Date', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Time', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'Status', 1, 1, 'L', true);

// Table data
foreach ($recent_appointments as $apt) {
    $pdf->Cell(60, 8, $apt['full_name'], 1);
    $pdf->Cell(40, 8, date('Y-m-d', strtotime($apt['preferred_date'])), 1);
    $pdf->Cell(40, 8, date('H:i', strtotime($apt['preferred_time'])), 1);
    $pdf->Cell(40, 8, ucfirst($apt['status']), 1, 1);
}

// Output the PDF
$pdf->Output('graduation_pictorial_report.pdf', 'D');
?>
