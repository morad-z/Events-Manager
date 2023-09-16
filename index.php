<!-- ////////////////////////////// Morad Zubidat : 208156828 & Amin Masharqa : 207358326 -->
<?php
$dbhost = "148.66.138.145";
$dbuser = "dbCourseSt23a";
$dbpass = "dbcourseShUsr23!";
$dbname = "dbCourseSt23";

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to assign a crew member to an event
function assignCrewToEvent($crewId, $eventId, $conn) {
    // Prepare the input parameters for the assignCrewToEvent procedure
    $crewId = (int)$crewId;
    $eventId = (int)$eventId;

    //an SQL statement that calls the assignCrewToEventProcedure procedure
    $sql = "CALL assignCrewToEventProcedure($crewId, $eventId)";

    // Execute the stored procedure
    if ($conn->multi_query($sql)) {
        $result = $conn->store_result();
        $row = $result->fetch_assoc();
        echo $row["message"];
        $result->free();
        if ($conn->more_results()) {
            $conn->next_result();
            if ($result = $conn->store_result()) {
                $row = $result->fetch_assoc();
                echo " Additional info: " . $row["message"];
                $result->free();
            }
        }
    } else {
        echo "Error executing the stored procedure: " . $conn->error;
    }
}

function applyDiscountToEvent($eventId, $discountPercent, $conn) {
    // Prepare the input parameters for the stored procedure
    $eventId = (int)$eventId;
    $discountPercent = (float)$discountPercent;

    //an SQL statement that calls the applyDiscountToEventProcedure procedure
    $sql = "CALL applyDiscountToEventProcedure($eventId, $discountPercent)";

    // Execute the stored procedure
    if ($conn->multi_query($sql)) {
        // Fetch the first result set (message) and display it
        $result = $conn->store_result();
        $row = $result->fetch_assoc();
        echo $row["message"];

        // Free the result set
        $result->free();
    } else {
        echo "Error executing the stored procedure: " . $conn->error;
    }
}


// Function to display all events in the last X weeks
function displayEventsLastXWeeks($weeks, $conn) {
    // Calculate the date X weeks ago from today
    $startDate = date("Y-m-d", strtotime("-{$weeks} weeks"));

    $query = "SELECT event_id, event_date, event_description, guests_number, event_price FROM dbCourseSt23.team31_Event_tbl1 WHERE event_date >= ? ORDER BY event_date DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $startDate);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $eventId, $eventDate, $eventDescription, $guestsNumber, $eventPrice); // Modified this line

    echo "<h2>Events in the Last {$weeks} Weeks</h2>";
    echo "<table>";
    echo "<tr><th>Event ID</th><th>Date</th><th>Description</th><th>Guests</th><th>Price</th></tr>";
    while (mysqli_stmt_fetch($stmt)) {
        echo "<tr>";
        echo "<td>" . $eventId . "</td>";
        echo "<td>" . $eventDate . "</td>";
        echo "<td>" . $eventDescription . "</td>";
        echo "<td>" . $guestsNumber . "</td>";
        echo "<td>" . $eventPrice . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    mysqli_stmt_close($stmt);
}

// Function to display active events (events that have not yet taken place)
function displayActiveEvents($conn) {
    $query = "SELECT event_id, event_date, event_description, guests_number, event_price FROM dbCourseSt23.team31_Event_tbl1 WHERE event_date >= CURDATE() ORDER BY event_date DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $eventId, $eventDate, $eventDescription, $guestsNumber, $eventPrice); // Modified this line

    echo "<h2>Active Events</h2>";
    echo "<table>";
    echo "<tr><th>Event ID</th><th>Date</th><th>Description</th><th>Guests</th><th>Price</th></tr>";
    while (mysqli_stmt_fetch($stmt)) {
        echo "<tr>";
        echo "<td>" . $eventId . "</td>";
        echo "<td>" . $eventDate . "</td>";
        echo "<td>" . $eventDescription . "</td>";
        echo "<td>" . $guestsNumber . "</td>";
        echo "<td>" . $eventPrice . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    mysqli_stmt_close($stmt);
}


// Function to display events that lack waiters and cooks
function displayEventsLackingStaff($conn) {
    $query = "SELECT * FROM dbCourseSt23.team31_Event_tbl1 WHERE event_id NOT IN (SELECT DISTINCT event_id FROM dbCourseSt23.team31_CREW_TBL1 WHERE role_ = 'waiter' AND role_ = 'chef')";
    $stmt = mysqli_query($conn, $query);

    // Display the events
    while ($row = mysqli_fetch_assoc($stmt)) {
        echo "Event ID: " . $row['event_id'] . ", Date: " . $row['event_date'] . ", Type: " . $row['event_description'] . "<br>";
    }
}

// Function to display repeat customers (customers with more than one event)
function displayRepeatCustomers($conn) {
    // Fetch customers with more than one event using prepared statement and join with client_tbl1
    $query = "SELECT c.first_name, c.last_name, COUNT(e.client_id) AS event_count 
              FROM dbCourseSt23.team31_Client_tbl1 c
              JOIN dbCourseSt23.team31_Event_tbl1 e ON c.client_id = e.client_id
              GROUP BY c.first_name, c.last_name
              HAVING COUNT(e.client_id) > 1";
    $stmt = mysqli_query($conn, $query);

    // Display the customers
    if (mysqli_num_rows($stmt) > 0) {
        echo "<h2>Repeat Customers:</h2>";
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($stmt)) {
            echo "<li>Guest Name: " . $row['first_name'] . " " . $row['last_name'] . ", Number of Events: " . $row['event_count'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "No repeat customers found.";
    }
}

// Function to display income for a specific salesperson in a certain month and year
function displayIncomeForSalesperson_team31($salespersonName, $month, $year, $conn) {
    // Split the full salesperson name into first name and last name
    $names = explode(" ", $salespersonName);
    $firstName = $names[0];
    $lastName = $names[1];
    $query = "SELECT calculateIncomeForSalespersonFunction_team31(?, ?, ?, ?) AS totalIncome";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssii", $firstName, $lastName, $month, $year);
    mysqli_stmt_execute($stmt);

    // Bind the result variable manually
    mysqli_stmt_bind_result($stmt, $totalIncome);

    // Fetch and display the total income
    if (mysqli_stmt_fetch($stmt)) {
        echo "Total income for $salespersonName in month $month and year $year: $totalIncome";
    } else {
        echo "No income information found for the specified salesperson.";
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}

// Function to display income X months back
function displayIncomeXMonthsBack($months, $conn) {
    // Calculate the date X months ago
    $dateXMonthsAgo = date('Y-m-d', strtotime("-$months months"));

    // Fetch total income for events that occurred X months ago using a prepared statement
    $query = "SELECT SUM(event_price) AS total_income FROM dbCourseSt23.team31_Event_tbl1 WHERE event_date >= ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $dateXMonthsAgo);
    mysqli_stmt_execute($stmt);

    // Bind the result variable manually
    mysqli_stmt_bind_result($stmt, $totalIncome);

    // Fetch and display the total income
    if (mysqli_stmt_fetch($stmt)) {
        echo "Total income for events in the last $months months: $totalIncome";
    } else {
        echo "No income information found for the specified period.";
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
function sendInvitation($salespersonId, $eventDate, $eventDescription, $guestsNumber, $mealPrice, $minimumPrice, $clientId, $conn) {
    // Calculate the price of the event
    $eventPrice = $minimumPrice + ($mealPrice * $guestsNumber);
    // Calculate the number of waiters and cooks required
    $waitersToGuestsRatio = 0.1;
    $cooksToGuestsRatio = 0.05;
    $numWaiters = ceil($guestsNumber * $waitersToGuestsRatio);
    $numCooks = ceil($guestsNumber * $cooksToGuestsRatio);
    // Check if the event date is available
    $availabilityQuery = "SELECT COUNT(*) AS count FROM dbCourseSt23.team31_Event_tbl1 WHERE event_date = ?";
    $availabilityStmt = mysqli_prepare($conn, $availabilityQuery);
    mysqli_stmt_bind_param($availabilityStmt, "s", $eventDate);
    mysqli_stmt_execute($availabilityStmt);
    $availabilityResult = mysqli_stmt_get_result($availabilityStmt);
    $availabilityRow = mysqli_fetch_assoc($availabilityResult);
    $eventExists = $availabilityRow["count"];
    if ($eventExists) {
        echo "The selected date is not available. Please choose another date.";
    } else {
        // Retrieve data of the salesperson
        $salespersonQuery = "SELECT * FROM dbCourseSt23.team31_CREW_TBL WHERE member_id = ?";
        $salespersonStmt = mysqli_prepare($conn, $salespersonQuery);
        mysqli_stmt_bind_param($salespersonStmt, "i", $salespersonId);
        mysqli_stmt_execute($salespersonStmt);
        $salespersonResult = mysqli_stmt_get_result($salespersonStmt);
        $salespersonRow = mysqli_fetch_assoc($salespersonResult);
        // Retrieve data of the customer
        $customerQuery = "SELECT * FROM dbCourseSt23.team31_Client_tbl1 WHERE client_id = ?";
        $customerStmt = mysqli_prepare($conn, $customerQuery);
        mysqli_stmt_bind_param($customerStmt, "i", $clientId);
        mysqli_stmt_execute($customerStmt);
        $customerResult = mysqli_stmt_get_result($customerStmt);
        $customerRow = mysqli_fetch_assoc($customerResult);
        // Insert the invitation into the database
        $invitationQuery = "INSERT INTO dbCourseSt23.team31_Event_tbl1 (salesMan_id, event_date, event_description, guests_number, meal_price, minimum_price, event_price, req_waiters, req_chefs, client_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $invitationStmt = mysqli_prepare($conn, $invitationQuery);
        mysqli_stmt_bind_param($invitationStmt, "isssddiiii", $salespersonId, $eventDate, $eventDescription, $guestsNumber, $mealPrice, $minimumPrice, $eventPrice, $numWaiters, $numCooks, $clientId);

        if (mysqli_stmt_execute($invitationStmt)) {
            $eventId = mysqli_insert_id($conn);

            // Display the invitation details
            echo "Invitation sent successfully!<br>";
            echo "Orderer (Customer) Details:<br>";
            echo "Name: " . ($customerRow['first_name'] ?? 'N/A') . " " . ($customerRow['last_name'] ?? 'N/A') . "<br>";
            echo "Phone: " . ($customerRow['phone_number'] ?? 'N/A') . "<br>";
            echo "Address: " . ($customerRow['address_city'] ?? 'N/A') . " " . ($customerRow['address_street'] ?? 'N/A') . " " . ($customerRow['address_bldg_NO'] ?? 'N/A') . "<br>";
            echo "<br>";
            echo "Salesperson Details:<br>";
            echo "Name: " . ($salespersonRow['first_name'] ?? 'N/A') . " " . ($salespersonRow['last_name'] ?? 'N/A') . "<br>";
            echo "Position: " . ($salespersonRow['role_'] ?? 'N/A') . "<br>";
            echo "Phone: " . ($salespersonRow['phone_number'] ?? 'N/A') . "<br>";
            echo "Address: " . ($salespersonRow['address_city'] ?? 'N/A') . " " . ($salespersonRow['address_street'] ?? 'N/A') . " " . ($salespersonRow['address_bldg_NO'] ?? 'N/A') . "<br>";
            echo "Unique ID: " . ($salespersonRow['member_id'] ?? 'N/A') . "<br>";
            echo "<br>";
            echo "Event Details:<br>";
            echo "Event ID: " . $eventId . "<br>";
            echo "Date: " . $eventDate . "<br>";
            echo "Event Description: " . $eventDescription . "<br>";
            echo "Number of Guests: " . $guestsNumber . "<br>";
            echo "Price of the Event: " . $eventPrice . "<br>";
            echo "Number of Waiters Required: " . $numWaiters . "<br>";
            echo "Number of Cooks Required: " . $numCooks . "<br>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// Function to handle the invitation process
function handleInvitation($conn) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["option"]) && $_POST["option"] == "9") {
            if (
                isset($_POST["salespersonId"]) &&
                isset($_POST["eventDate"]) &&
                isset($_POST["eventDescription"]) &&
                isset($_POST["guestsNumber"]) &&
                isset($_POST["mealPrice"]) &&
                isset($_POST["minimumPrice"]) &&
                isset($_POST["clientId"])
            ) {
                $salespersonId = $_POST["salespersonId"];
                $eventDate = $_POST["eventDate"];
                $eventDescription = $_POST["eventDescription"];
                $guestsNumber = $_POST["guestsNumber"];
                $mealPrice = $_POST["mealPrice"];
                $minimumPrice = $_POST["minimumPrice"];
                $clientId = $_POST["clientId"]; 

                // Call the function to send the invitation
                sendInvitation($salespersonId, $eventDate, $eventDescription, $guestsNumber, $mealPrice, $minimumPrice, $clientId, $conn);
            } else {
                echo "Please fill in all the required fields.";
            }
        }
    }
}
?>
<?php
 function showAssignCrewToEventForm()
 {
     echo "Please enter Crew ID and Event ID.";
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="1">';
     echo '<input type="text" name="crewId" class="form-control" placeholder="Crew ID">';
     echo '<input type="text" name="eventId" class="form-control mt-2" placeholder="Event ID">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Assign">';
     echo '</form>';
 }

 function showApplyDiscountToEventForm()
 {
     echo "Please enter Event ID and Discount Percent.";
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="2">';
     echo '<input type="text" name="eventId" class="form-control" placeholder="Event ID">';
     echo '<input type="text" name="discountPercent" class="form-control mt-2" placeholder="Discount Percent">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Apply Discount">';
     echo '</form>';
 }


 function showDisplayEventsLastXWeeksForm()
 {   
     echo "Please enter the number of weeks.";
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="3">';
     echo '<input type="text" name="weeks" class="form-control" placeholder="Number of weeks">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Display Events">';
     echo '</form>';
 }

 function showDisplayIncomeForSalespersonForm()
 {   echo "Please enter Salesperson Name, Month and Year.";
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="7">';
     echo '<input type="text" name="salespersonName" class="form-control" placeholder="Salesperson Name">';
     echo '<input type="text" name="month" class="form-control mt-2" placeholder="Month">';
     echo '<input type="text" name="year" class="form-control mt-2" placeholder="Year">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Display Income">';
     echo '</form>';
 }

 function showDisplayIncomeXMonthsBackForm()
 {  echo "Please enter the number of months.";
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="8">';
     echo '<input type="text" name="months" class="form-control" placeholder="Number of months">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Display Income X Months Back">';
     echo '</form>';
 }
 function showSendInvitationForm()
 {
     echo '<form method="post" class="mt-3">';
     echo '<input type="hidden" name="option" value="9">';
     echo '<input type="text" name="salespersonId" class="form-control" placeholder="Salesperson ID">';
     echo '<input type="date" name="eventDate" class="form-control mt-2" placeholder="Event Date">';
     echo '<input type="text" name="eventDescription" class="form-control mt-2" placeholder="Event Description">';
     echo '<input type="number" name="guestsNumber" class="form-control mt-2" placeholder="Number of Guests">';
     echo '<input type="number" name="mealPrice" class="form-control mt-2" placeholder="Meal Price">';
     echo '<input type="number" name="minimumPrice" class="form-control mt-2" placeholder="Minimum Price">';
     echo '<input type="number" name="clientId" class="form-control mt-2" placeholder="Client ID">';
     echo '<input type="submit" class="btn btn-primary mt-2" value="Send Invitation">';
     echo '</form>';
 }
?>
<!DOCTYPE html>
<html>

<head>
    <title>Events Manager</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
    .no-margin {
        margin: 0;
    }

    #first_inp {
        margin-left: 23px;
        width: 50%;
    }

    #first-sub {
        margin-left: 23px;
    }

    .form-control {
        width: 50%;
    }
    </style>

</head>

<body>

    <div class="container mt-5">
        <h1 class="text-center">Events Manager</h1>
        <div class="mt-5" id="main_menu">
            <?php
            // Display options
            echo "<ol >";
            echo "<li>Assign crew member to event</li>";
            echo "<br>";
            echo "<li>Make discount for event</li>";
            echo "<br>";
            echo "<li>Display all events in the last X weeks</li>";
            echo "<br>";
            echo "<li>Display active events</li>";
            echo "<br>";
            echo "<li>Display events that lack waiters and cooks</li>";
            echo "<br>";
            echo "<li>Display repeat customers</li>";
            echo "<br>";
            echo "<li>Display income for a specific salesperson in a certain month and year</li>";
            echo "<br>";
            echo "<li>Display income X months back</li>";
            echo "<br>";
            echo "<li>Creat New Event</li>";
            echo "</ol>";
            echo "<form method='post'>";
            echo "<input type='text' name='option' class='form-control' placeholder='Enter option number' id='first_inp'>";
            echo "<br>";
            echo "<input type='submit' class='btn btn-primary mt-2' value='Submit' id='first-sub'>";
            echo "</form>";
            ?>
        </div>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST["option"])) {
                $option = $_POST["option"];
                switch ($option) {
                    
                    case '1':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        showAssignCrewToEventForm();
                        if (isset($_POST["crewId"]) && isset($_POST["eventId"])) {
                            $crewId = $_POST["crewId"];
                            $eventId = $_POST["eventId"];
                            assignCrewToEvent($crewId, $eventId, $conn);
                        }
                        break;
                    case '2':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        showApplyDiscountToEventForm();
                        if (isset($_POST["eventId"]) && isset($_POST["discountPercent"])) {
                            $eventId = $_POST["eventId"];
                            $discountPercent = $_POST["discountPercent"];
                            applyDiscountToEvent($eventId, $discountPercent, $conn);
                        }
                        break;

                    case '3':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        showDisplayEventsLastXWeeksForm();
                        if (isset($_POST["weeks"])) {
                            $weeks = $_POST["weeks"];
                            displayEventsLastXWeeks($weeks, $conn);
                        }
                        break;
                    case '4':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";

                        displayActiveEvents($conn);
                        break;
                    case '5':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        displayEventsLackingStaff($conn);
                        break;
                    case '6':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        displayRepeatCustomers($conn);
                        break;
                    case '7':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        showDisplayIncomeForSalespersonForm();
                        if (isset($_POST["salespersonName"]) && isset($_POST["month"]) && isset($_POST["year"])) {
                            $salespersonName = $_POST["salespersonName"];
                            $month = $_POST["month"];
                            $year = $_POST["year"];
                            displayIncomeForSalesperson_team31($salespersonName, $month, $year, $conn);
                        }
                        break;
                    case '8':
                        echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                        showDisplayIncomeXMonthsBackForm();
                        if (isset($_POST["months"])) {
                            $months = $_POST["months"];
                            displayIncomeXMonthsBack($months, $conn);
                        }
                        break;
                        case '9':
                            echo "<script>document.getElementById('main_menu').style.display = 'none';</script>";
                            showSendInvitationForm();
                            handleInvitation($conn);
                            break;
                    default:
                        echo "Invalid option selected. Try again.";
                        break;
                }
            }
        }
        ?>
    </div>

    </body>
</html>