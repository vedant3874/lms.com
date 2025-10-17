<?php include 'header.php'; include 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') { header('Location: login.php'); exit; }
if (!file_exists('fpdf.php')) { echo '<div class="card">FPDF library not found. Please download from http://www.fpdf.org and place fpdf.php here.</div>'; include 'footer.php'; exit; }
require('fpdf.php');
$student = htmlspecialchars($_SESSION['user']['name']);
$course_id = intval($_GET['course_id']);
$res = $conn->query("SELECT title FROM courses WHERE id=$course_id");
$course = $res->fetch_assoc()['title'];
$pdf = new FPDF(); $pdf->AddPage(); $pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Certificate of Completion',0,1,'C'); $pdf->Ln(20);
$pdf->SetFont('Arial','',12); $pdf->Cell(0,10,"This certifies that $student has completed the course:",0,1,'C');
$pdf->SetFont('Arial','I',14); $pdf->Cell(0,10,$course,0,1,'C'); $pdf->Ln(30);
$pdf->Cell(0,10,'Date: '.date('Y-m-d'),0,1,'C'); $pdf->Output(); ?>