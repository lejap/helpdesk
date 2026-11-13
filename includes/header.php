<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <title>BC | Helpdesk</title>
    <link rel="icon" type="image/png" href="assets/images/proflogo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #00b4d8;
            --light-blue: #caf0f8;
            --dark-blue: #0077b6;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #caf0f8 0%, #90e0ef 50%, #00b4d8 100%);
            background-attachment: fixed; /* Ensures gradient stays full screen */
            min-height: 100vh;
            color: #333;
            overflow-x: hidden; /* Prevent horizontal scroll on mobile */
        }

        /* --- Global Component Styling --- */

        /* Cards (Glassmorphism) */
        .card {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(4px);
            border: var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        /* Tables */
        .table {
            background-color: transparent !important;
            margin-bottom: 0;
        }
        .table thead {
            background-color: var(--light-blue);
            color: var(--dark-blue);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 180, 216, 0.1);
        }
        /* Ensure tables scroll on mobile */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            font-weight: 500;
            padding: 8px 16px;
        }
        .btn-primary:hover {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 180, 216, 0.25);
        }

        /* Titles & Badges */
        h1, h2, h3, h4, h5 {
            color: var(--dark-blue);
            font-weight: 600;
        }
        
        body > .container, body > .container-fluid {
            padding-bottom: 3rem;
            padding-left: 15px;
            padding-right: 15px;
        }

        /* --- RESPONSIVE ADJUSTMENTS --- */
        
        /* Mobile Devices (Phones) */
        @media (max-width: 768px) {
            h1 { font-size: 1.75rem; }
            h2 { font-size: 1.5rem; }
            h3 { font-size: 1.25rem; }
            
            .card {
                margin-bottom: 15px;
            }
            
            .btn {
                width: 100%; /* Make buttons full width on mobile for easier tapping */
                margin-bottom: 5px;
            }
            
            /* Adjust table font size for mobile */
            .table {
                font-size: 0.9rem;
            }
            
            /* Ensure container has breathing room */
            body > .container {
                padding-left: 20px;
                padding-right: 20px;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 576px) {
            .form-control, .form-select {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>