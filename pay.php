<?php
/*
 * Simple PHP Payment Gateway Clone
 * Displays a clean, minimal payment interface.
 * This is a frontend + backend combined example.
 * No real payment processing - mimics payment flow.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate payment processing delay
    sleep(2);

    // Validate input (amount required and positive numeric)
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    $errors = [];
    if (!$amount || $amount <= 0) {
        $errors[] = 'Please enter a valid payment amount.';
    }
    if (!$name) {
        $errors[] = 'Name is required.';
    }
    if (!$email) {
        $errors[] = 'Valid email is required.';
    }

    if (empty($errors)) {
        // Generate fake payment ID
        $payment_id = 'PAY' . strtoupper(bin2hex(random_bytes(4)));

        // Normally, here you would process the payment with a gateway API

        // Show success message
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Payment Gateway Clone</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap');
    :root {
      --bg-color: #ffffff;
      --text-primary: #111827;
      --text-secondary: #6b7280;
      --accent-color: #111827;
      --border-radius: 0.75rem;
      --shadow-light: rgba(0,0,0,0.05);
      --font-headline: 'Poppins', sans-serif;
      --font-body: system-ui, sans-serif;
    }
    * {
      box-sizing: border-box;
    }
    body {
      margin:0; padding:0;
      background: var(--bg-color);
      font-family: var(--font-body);
      color: var(--text-secondary);
      display: flex;
      min-height: 100vh;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    main {
      max-width: 420px;
      width: 100%;
      background: var(--bg-color);
      border-radius: var(--border-radius);
      box-shadow: 0 12px 24px var(--shadow-light);
      padding: 2.5rem 2rem;
      text-align: center;
    }
    h1 {
      font-family: var(--font-headline);
      font-weight: 600;
      font-size: 3rem;
      margin-bottom: 1rem;
      color: var(--text-primary);
    }
    p.subtitle {
      margin-bottom: 2rem;
      font-size: 1.125rem;
      color: var(--text-secondary);
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      color: var(--text-primary);
    }
    label {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 0.3rem;
      display: block;
      text-align: left;
    }
    input[type="text"],
    input[type="email"],
    input[type="number"] {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      border-radius: var(--border-radius);
      border: 1.5px solid #d1d5db;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus {
      border-color: #2563eb;
      outline: none;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
    }
    .btn-submit {
      background: var(--accent-color);
      color: white;
      border: none;
      padding: 0.85rem 0;
      border-radius: var(--border-radius);
      font-weight: 600;
      font-size: 1.25rem;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.3s ease;
      margin-top: 1rem;
    }
    .btn-submit:hover {
      background-color: #1e40af;
      transform: scale(1.05);
    }
    .message {
      margin-top: 1rem;
      font-size: 1rem;
      font-weight: 600;
    }
    .error {
      color: #dc2626; /* Red */
      text-align: left;
      margin-bottom: 1rem;
    }
    .success {
      color: #16a34a; /* Green */
    }
  </style>
</head>
<body>
  <main>
    <h1>Secure Payment</h1>
    <p class="subtitle">Enter your payment details below to complete the checkout securely.</p>
    <?php if (!empty($errors)): ?>
      <div class="message error" role="alert">
        <?php foreach($errors as $error): ?>
          &bull; <?=htmlspecialchars($error)?><br />
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="message success" role="alert">
        Payment successful!<br />
        Payment ID: <strong><?=htmlspecialchars($payment_id)?></strong>
      </div>
    <?php else: ?>
      <form method="post" novalidate>
        <label for="name">Full Name</label>
        <input id="name" name="name" type="text" placeholder="John Doe" required value="<?=htmlspecialchars($_POST['name'] ?? '')?>" />
        
        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" placeholder="john@example.com" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>" />
        
        <label for="amount">Amount (USD)</label>
        <input id="amount" name="amount" type="number" min="1" step="0.01" placeholder="50.00" required value="<?=htmlspecialchars($_POST['amount'] ?? '')?>" />
        
        <button type="submit" class="btn-submit">Pay Now</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>

