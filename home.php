<?php
include 'config.php'; // Make sure this file contains your database connection ($conn)
session_start();

// --- AJAX Booking Handler ---
// This block handles the AJAX request from the JavaScript after fake payment
if (isset($_POST['action']) && $_POST['action'] == 'book_room') {
    // It's crucial to set the content type to JSON for the response
    header('Content-Type: application/json');

    // Check if user is logged in (security measure)
    if (!isset($_SESSION['usermail'])) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
        exit();
    }

    // Retrieve and sanitize POST data
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $country = mysqli_real_escape_string($conn, $_POST['Country']);
    $phone = mysqli_real_escape_string($conn, $_POST['Phone']);
    $roomType = mysqli_real_escape_string($conn, $_POST['RoomType']);
    $bed = mysqli_real_escape_string($conn, $_POST['Bed']);
    $noofRoom = mysqli_real_escape_string($conn, $_POST['NoofRoom']);
    $meal = mysqli_real_escape_string($conn, $_POST['Meal']);
    $cin = mysqli_real_escape_string($conn, $_POST['cin']);
    $cout = mysqli_real_escape_string($conn, $_POST['cout']);

    // Server-side validation
    if (empty($name) || empty($email) || empty($phone) || empty($roomType) || empty($cin) || empty($cout)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit();
    }
    
    // Check if check-in date is not in the past
    if (new DateTime($cin) < new DateTime('today')) {
        echo json_encode(['status' => 'error', 'message' => 'Check-in date cannot be in the past.']);
        exit();
    }

    // Check if checkout date is after check-in date
    if (new DateTime($cout) <= new DateTime($cin)) {
        echo json_encode(['status' => 'error', 'message' => 'Check-out date must be after the check-in date.']);
        exit();
    }

    // Calculate number of days
    $date1 = new DateTime($cin);
    $date2 = new DateTime($cout);
    $interval = $date1->diff($date2);
    $nodays = $interval->days;

    // The 'new' status indicates a new, confirmed booking
    $status = 'new';

    $sql = "INSERT INTO `roombook`(`Name`, `Email`, `Country`, `Phone`, `RoomType`, `Bed`, `NoofRoom`, `Meal`, `cin`, `cout`, `nodays`, `stat`) VALUES ('$name','$email','$country','$phone','$roomType','$bed','$noofRoom','$meal','$cin','$cout','$nodays', '$status')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Room booked successfully!']);
    } else {
        // Provide a more specific error for debugging if possible
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit(); // Terminate script execution after handling AJAX request
}

// --- Regular Page Load ---
$usermail = "";
// Check if session variable is set before using it to avoid warnings
if (isset($_SESSION['usermail'])) {
    $usermail = $_SESSION['usermail'];
} else {
    header("location: index.php");
    exit(); // It's good practice to exit after a redirect header
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/home.css">
    <title>Hotel Blue Bird</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="./admin/css/roombook.css">
    <style>
        /* Styles for the main reservation panel */
        #guestdetailpanel {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }
        #guestdetailpanel .middle {
            height: 450px;
        }

        /* Styles for the NEW Fake Payment Popup */
        #payment-popup {
            display: none; /* Hidden by default */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1100;
            width: 90%;
            max-width: 400px;
            padding: 20px;
        }
        .payment-popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
         .payment-popup-header h4 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
           }
        .payment-popup-body .form-group {
            margin-bottom: 15px;
        }
        .payment-popup-body label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .payment-popup-body input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .payment-popup-footer {
            text-align: right;
            margin-top: 20px;
        }
        
        /* UPDATED Footer Style */
        .site-footer {
            border-top: 1px solid #444; /* Subtle separator line */
            background-color: #343a40 !important; /* Added to ensure dark background */
        }

        /* Styles for Payment Options (Card/UPI) */
        .payment-options {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .payment-option-btn {
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 1rem;
            color: #495057;
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }
        .payment-option-btn.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }    
        
        .roomselect {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        margin: 20px 0;
    }

    .roombox {
        background: #ffffff; /* White background for room boxes */
        border-radius: 15px;
        box-shadow: 0 4px 8px hsl(133, 91.70%, 52.90%);
        margin: 15px;
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition */
        width: 250px; /* Width of room boxes */
        height: 300px; /* Height of room boxes */
        position: relative;
        overflow: hidden;
    }

    .roombox:hover {
        transform: translateY(-5px); /* Slight lift on hover */
        box-shadow: 0 8px 16px rgb(52, 246, 65); /* Deeper shadow on hover */
    }

    .roomdata h2 {
        margin: 10px 0;
        font-size: 1.5rem;
        color:#fff; /* Dark text for readability */
    }

    .services {
        margin: 10px 0;
    }

    .services i {
        margin: 0 5px;
        font-size: 1.5rem; /* Increased icon size */
        color: #007bff; /* Bootstrap primary color */
    }

    .btn-primary {
        background-color: #007bff; /* Primary button color */
        border-color: #007bff; /* Button border color */
        color: #fff; /* White text color */
    }

    .btn-primary:hover {
        background-color: #0056b3; /* Darker blue on hover */
        border-color: #0056b3; /* Darker border on hover */
    }
.facility {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        margin: 20px 0;
    }

    .box {
        background:rgba(144, 118, 236, 0.07); /* White background for facility boxes */
        border-radius: 25px;
        border-color: #495057;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        margin: 15px;
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition */
        width: 200px; /* Width of facility boxes */
        height: 100px; /* Height of facility boxes */
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .box:hover {
        transform: translateY(-5px); /* Slight lift on hover */
        box-shadow: 0 8px 16px rgba(48, 4, 98, 0.3); /* Deeper shadow on hover */
        cursor: pointer;
    }

     h3 {
        background-color: transparent;
        margin: 0; /* Remove default margin */
        font-size: 2.5rem; /* Font size for headings */
        color:white; /* White text color */
        text-shadow: 0 0 5px rgba(255, 255, 255, 0.8), /* White glow */
                     0 0 10px rgba(81, 77, 77, 0.41); /* Stronger white glow */
        transition: text-shadow 0.3s ease; /* Smooth transition for glow */
    }
    .box:hover h3 {
        text-shadow: 0 0 10px rgb(235, 22, 22), /* Cyan glow */
                     0 0 20px rgb(26, 255, 0); /* Stronger cyan glow */
    }

        
    </style>
</head>

<body>
<nav>
    <div class="logo">
        <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo">
        <p>BLUEBIRD</p>
    </div>
    <ul>
        <li><a href="#firstsection">Home</a></li>
        <li><a href="#secondsection">Rooms</a></li>
        <li><a href="#thirdsection">Facilities</a></li>
        <li><a href="#contactus">Contact Us</a></li>
        <a href="./logout.php"><button class="btn btn-danger">Logout</button></a>
    </ul>
</nav>

<section id="firstsection" class="carousel slide carousel_section" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img class="carousel-image" src="./image/hotel1.jpg" alt="Hotel Exterior">
        </div>
        <div class="carousel-item">
            <img class="carousel-image" src="./image/hotel2.jpg" alt="Hotel Lobby">
        </div>
        <div class="carousel-item">
            <img class="carousel-image" src="./image/hotel3.jpg" alt="Hotel Room">
        </div>
        <div class="carousel-item">
            <img class="carousel-image" src="./image/hotel4.jpg" alt="Hotel Pool">
        </div>

        <div class="welcomeline">
            <h1 class="welcometag">Welcome to heaven on earth</h1>
        </div>

        <div id="guestdetailpanel">
            <form id="reservationForm" class="guestdetailpanelform">
                <div class="head">
                    <h3>RESERVATION</h3>
                    <i class="fa-solid fa-circle-xmark" onclick="closebox()"></i>
                </div>
                <div class="middle">
                    <div class="guestinfo">
                        <h4>Guest Information</h4>
                        <input type="text" name="Name" placeholder="Enter Full Name" required>
                        <input type="email" name="Email" placeholder="Enter Email" required>

                        <?php
                        $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
                        ?>
                        <select name="Country" class="selectinput" required>
                            <option value="" selected disabled>Select your country</option>
                            <?php
                            foreach ($countries as $value):
                                echo '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</option>';
                            endforeach;
                            ?>
                        </select>
                        <input type="tel" name="Phone" placeholder="Enter Phone No" required>
                    </div>

                    <div class="line"></div>

                    <div class="reservationinfo">
                        <h4>Reservation Information</h4>
                        <select name="RoomType" class="selectinput" required>
                            <option value="" selected disabled>Type Of Room</option>
                            <option value="Superior Room">SUPERIOR ROOM</option>
                            <option value="Deluxe Room">DELUXE ROOM</option>
                            <option value="Guest House">GUEST HOUSE</option>
                            <option value="Single Room">SINGLE ROOM</option>
                        </select>
                        <select name="Bed" class="selectinput" required>
                            <option value="" selected disabled>Bedding Type</option>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Quad">Quad</option>
                            <option value="None">None</option>
                        </select>
                        <select name="NoofRoom" class="selectinput" required>
                            <option value="" selected disabled>No of Room</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="Meal" class="selectinput" required>
                            <option value="" selected disabled>Meal</option>
                            <option value="Room only">Room only</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Half Board">Half Board</option>
                            <option value="Full Board">Full Board</option>
                        </select>
                        <div class="datesection">
                            <span>
                                <label for="cin">Check-In</label>
                                <input id="cin" name="cin" type="date" required>
                            </span>
                            <span>
                                <label for="cout">Check-Out</label>
                                <input id="cout" name="cout" type="date" required>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="footer">
                    <button type="button" class="btn btn-primary" id="proceedToPayBtn">Proceed to Payment</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section id="secondsection">
    <img src="./image/homeanimatebg.svg" alt="">
    <div class="ourroom">
        <h1 class="head">≼ Our Room ≽</h1>
       <div class="roomselect">
    <div class="roombox">
        <div class="hotelphoto h1"></div>
        <div class="roomdata">
            <h2>Superior Room</h2>
            <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
                <i class="fa-solid fa-dumbbell"></i>
                <i class="fa-solid fa-person-swimming"></i>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
        </div>
    </div>
    <div class="roombox">
        <div class="hotelphoto h2"></div>
        <div class="roomdata">
            <h2>Deluxe Room</h2>
            <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
        </div>
    </div>
    <div class="roombox">
        <div class="hotelphoto h3"></div>
        <div class="roomdata">
            <h2>Guest Room</h2>
            <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
        </div>
    </div>
    <div class="roombox">
        <div class="hotelphoto h4"></div>
        <div class="roomdata">
            <h2>Single Room</h2>
            <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox()">Book</button>
        </div>
    </div>
</div>

    </div>
</section>

<section id="thirdsection">
    <h1 class="head">≼ Facilities ≽</h1>
    <div class="facility" >
        <div class="box" style="border-radius: 25px;"><h3>Swimming Pool</h3></div>
        <div class="box" style="border-radius: 25px;"><h3>Spa</h3></div>
        <div class="box" style="border-radius: 25px;"><h3>24*7 Restaurants</h3></div>
        <div class="box" style="border-radius: 25px;"><h3>24*7 Gym</h3></div>
        <div class="box" style="border-radius: 25px;"><h3>Heli Service</h3></div>
    </div>
</section>

<div id="payment-popup">
    <div class="payment-popup-header">
        <h4>Complete Your Payment</h4>
        <button type="button" class="btn-close" id="closePaymentPopup"></button>
    </div>
    <div class="payment-popup-body">
        <div class="payment-options">
            <button id="card-option" class="payment-option-btn active">Card</button>
            <button id="upi-option" class="payment-option-btn">UPI</button>
        </div>

        <div id="card-payment" class="payment-details">
            <p>This is a simulated payment gateway. No real transaction will be made.</p>
            <div class="form-group">
                <label for="fakeCardNumber">Card Number</label>
                <input type="text" id="fakeCardNumber" value="4242 4242 4242 4242" readonly>
            </div>
            <div class="form-group">
                <label for="fakeExpiry">Expiry Date</label>
                <input type="text" id="fakeExpiry" value="12/26" readonly>
            </div>
            <div class="form-group">
                <label for="fakeCvv">CVV</label>
                <input type="text" id="fakeCvv" value="123" readonly>
            </div>
        </div>

        <div id="upi-payment" class="payment-details" style="display: none;">
            <p>Scan the QR code or use the UPI ID below.</p>
            <img src="https://placehold.co/150x150/eee/ccc?text=Fake+QR+Code" alt="Fake QR Code for UPI payment" style="display:block; margin: 20px auto; border-radius: 5px;">
            <p style="text-align:center; font-weight: bold; font-size: 1.1rem;">hotel.bluebird@fakeupi</p>
        </div>
    </div>
    <div class="payment-popup-footer">
        <button type="button" class="btn btn-success" id="confirmPaymentBtn">Confirm & Book</button>
    </div>
</div>

<footer id="contactus" class="site-footer text-light bg-dark pt-5 pb-4" style="width: 100%; height: fit-content; padding: 100px 0;">
    <div class="container text-md-left">
        <div class="row text-md-left">
            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
                <h3 class="text-uppercase mb-4 font-weight-bold text-warning">Hotel Blue Bird</h3>
                <p>Experience luxury and comfort like never before. Nestled in the heart of nature with modern amenities and top-class service.</p>
            </div>
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold text-warning">Quick Links</h5>
                <p><a href="#firstsection" class="text-light text-decoration-none">Home</a></p>
                <p><a href="#secondsection" class="text-light text-decoration-none">Rooms</a></p>
                <p><a href="#thirdsection" class="text-light text-decoration-none">Facilities</a></p>
                <p><a href="#contactus" class="text-light text-decoration-none">Contact Us</a></p>
            </div>
            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold text-warning">Contact</h5>
                <p><i class="fas fa-home me-3"></i> 123 Paradise Road, Mumbai, India</p>
                <p><i class="fas fa-envelope me-3"></i> contact@bluebirdhotel.com</p>
                <p><i class="fas fa-phone me-3"></i> +91 98765 43210</p>
                <p><i class="fas fa-print me-3"></i> +91 98765 43211</p>
            </div>
            <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 font-weight-bold text-warning">Follow us</h5>
                <a class="btn btn-outline-light btn-floating m-1" href="#"><i class="fab fa-facebook-f"></i></a>
                <a class="btn btn-outline-light btn-floating m-1" href="#"><i class="fab fa-instagram"></i></a>
                <a class="btn btn-outline-light btn-floating m-1" href="#"><i class="fab fa-twitter"></i></a>
                <a class="btn btn-outline-light btn-floating m-1" href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
        <div class="row d-flex justify-content-center mt-4">
            <div class="col-md-7 col-lg-8 text-center">
                <p class="text-light">© 2025 Hotel Blue Bird. All rights reserved. | Designed by <strong>Piyush Agre & Devesh Thawali</strong></p>
            </div>
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<script>
    const guestDetailPanel = document.getElementById("guestdetailpanel");
    const paymentPopup = document.getElementById("payment-popup");
    const reservationForm = document.getElementById("reservationForm");

    function openbookbox() {
        guestDetailPanel.style.display = "flex";
    }

    function closebox() {
        guestDetailPanel.style.display = "none";
    }

    // --- Date Picker Logic ---
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("cin").setAttribute('min', today);
    document.getElementById("cout").setAttribute('min', today);

    // --- Payment Flow Logic ---
    document.getElementById("proceedToPayBtn").addEventListener("click", function() {
        if (reservationForm.checkValidity()) {
            paymentPopup.style.display = "block";
        } else {
            reservationForm.reportValidity();
        }
    });
    
    document.getElementById("closePaymentPopup").addEventListener("click", function() {
        paymentPopup.style.display = "none";
    });

    // --- UPI/Card Tab Switching ---
    const cardOption = document.getElementById('card-option');
    const upiOption = document.getElementById('upi-option');
    const cardPaymentDetails = document.getElementById('card-payment');
    const upiPaymentDetails = document.getElementById('upi-payment');

    cardOption.addEventListener('click', () => {
        cardOption.classList.add('active');
        upiOption.classList.remove('active');
        cardPaymentDetails.style.display = 'block';
        upiPaymentDetails.style.display = 'none';
    });

    upiOption.addEventListener('click', () => {
        upiOption.classList.add('active');
        cardOption.classList.remove('active');
        upiPaymentDetails.style.display = 'block';
        cardPaymentDetails.style.display = 'none';
    });

    // --- Form Submission on Final Confirmation ---
    document.getElementById("confirmPaymentBtn").addEventListener("click", function() {
        swal({
            title: "Processing...",
            text: "Please wait while we confirm your booking.",
            icon: "info",
            buttons: false,
            closeOnClickOutside: false,
        });

        const formData = new FormData(reservationForm);
        formData.append('action', 'book_room');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                swal("Booked!", data.message, "success")
                .then(() => {
                    paymentPopup.style.display = 'none';
                    closebox();
                    reservationForm.reset();
                });
            } else {
                swal("Error!", data.message, "error");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            swal("Oops!", "Something went wrong. Please try again.", "error");
        });
    });

</script>
</body>
</html>