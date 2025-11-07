<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Student Receipt</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      padding-top: 50px;
    }
    .container {
      max-width: 500px;
    }
    .card {
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .status {
      margin-top: 15px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="card p-4">
    <h4 class="mb-3 text-center">Upload Receipt to Student</h4>

    <form id="receiptForm">
      <div class="mb-3">
        <label for="student_id" class="form-label">Student ID</label>
        <input type="text" class="form-control" id="student_id" placeholder="Ex. 21019876" required>
      </div>

      <div class="mb-3">
        <label for="receipt_file" class="form-label">Receipt Image</label>
        <input type="file" class="form-control" id="receipt_file" accept="image/*" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Upload Receipt</button>
    </form>

    <div class="status text-center mt-3" id="status"></div>
  </div>
</div>

<script>
  document.getElementById('receiptForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const studentId = document.getElementById('student_id').value;
    const fileInput = document.getElementById('receipt_file');
    const status = document.getElementById('status');

    if (fileInput.files.length === 0) {
      status.textContent = "Please select a receipt image.";
      status.className = "status text-danger";
      return;
    }

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onloadend = async function () {
      const base64Data = reader.result;

      try {
        const response = await fetch('api/send_receipts_data_to_students.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-API-KEY': 'A1B2C3DAKO4E5A1B2C3DSI4E5A1B2C3DJOSHUA4E5F6GPOGI7H8I9J0'
          },
          body: JSON.stringify({
            student_id: studentId,
            receipts: base64Data
          })
        });

        const result = await response.json();
        if (response.ok) {
          status.className = "status text-success";
          status.textContent = "✅ " + result.message;
        } else {
          status.className = "status text-danger";
          status.textContent = "❌ " + (result.error || "Unknown error");
        }
      } catch (err) {
        status.className = "status text-danger";
        status.textContent = "❌ Failed to send request.";
      }
    };

    reader.readAsDataURL(file);
  });
</script>

</body>
</html>