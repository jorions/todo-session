<?php

//declare (initialize?) starting variables
$db = new mysqli("localhost", "root", "root", "portfolio_todo");
$fullTodoList = "SELECT * FROM todo_list";
$fullCompleteList = "SELECT * FROM complete_list";
$resultTodo = $db->query($fullTodoList); //query and prepare/execute are functionally identical, but prepare/execute permits you to use PHP variables, while query does not
$resultComplete = $db->query($fullCompleteList);
$updatedList = FALSE;

//database connection error handling
if($db->connect_errno) {
    echo "Failed to connect to MySQL :(<br>";
    echo $db->connect_error;
    exit();
}

//adding item to list
if(isset($_POST["submit"])) {
    if ($_POST["todo"] != "") {
        $stmt = $db->prepare("INSERT INTO todo_list (item, date) VALUES (?, ?)"); //could also use the MYSQL function NOW() for the date ("INSERT INTO todo_list (item, date) VALUES (?, NOW());
        $item = $_POST['todo'];
        $date = date("d F Y");
        $stmt->bind_param("ss", $item, $date);
        $stmt->execute();
        $updatedList = TRUE;
    }
}

//resetting both lists
if (isset($_POST["reset"])) {
    $db->query("TRUNCATE TABLE todo_list");
    $db->query("TRUNCATE TABLE complete_list");
    $updatedList = TRUE;
}

//deleting item from list
$resultTodo = $db->query($fullTodoList);
if($resultTodo) {
    $stmt = $db->prepare("DELETE FROM todo_list WHERE id=?");
    foreach($resultTodo as $row) {
        if(isset($_POST["remove-" . $row["id"]])) {
            if($_POST["remove-" . $row["id"]] == "1") {
                $stmt->bind_param("i", $row["id"]);
                $stmt->execute();
                $updatedList = TRUE;
            }
        }
    }
}

//adding item to complete list
$resultTodo = $db->query($fullTodoList);
if($resultTodo) {
    $stmtMove = $db->prepare("INSERT INTO complete_list SELECT * FROM todo_list WHERE id=?");
    $stmtRemove = $db->prepare("DELETE FROM todo_list WHERE id=?");
    $stmtUpdate = $db->prepare("UPDATE complete_list SET date=? WHERE id=?");
    foreach($resultTodo as $row) {
        if(isset($_POST["complete-" . $row["id"]])) {
            if($_POST["complete-" . $row["id"]] == "1") {
                $stmtMove->bind_param("i", $row["id"]);
                $stmtMove->execute();
                $newDate = date("d F Y");
                $newID = $db->insert_id;
                $stmtRemove->bind_param("i", $row["id"]);
                $stmtUpdate->bind_param("si", $newDate, $newID);
                $stmtRemove->execute();
                $stmtUpdate->execute();
                $updatedList = TRUE;
            }
        }
    }
}


//prompt user to alter list before hitting submit
if($updatedList == FALSE) {
    echo "Please enter something into the todo list";
}


/*
if($resultTodo) {
    foreach($resultTodo as $row) {
        $itemDelete = $row["item"];
        if(isset($_POST["remove"])) {
            if($_POST["remove"] == $row["item"]) {
                //if(!empty($_POST["Remove-$itemDelete"])) {
                $stmt = $db->prepare("DELETE FROM todo_list WHERE item=?");
                $stmt->bind_param("s", $itemDelete);
                $stmt->execute();
                //$db->query("DELETE FROM todo_list WHERE item = " . $itemDelete);
                $db->query("ALTER TABLE todo_list AUTO_INCREMENT= 1");
            }
        }
    }
    //$db->query("TRUNCATE TABLE todo_list"); //IS THERE A REASON TO USE "PREPARE" and "EXECUTE" ELSEWHERE INSTEAD OF "QUERY" AS USED HERE?
}
*/

?>

<form action="Day8-TodoList.php"
      method="POST" style="font-family: Arial">

    <input type="text" name="todo">
    <input type="submit" name="submit">
    <input type="submit" name="reset" value="Reset">
    <br><br>
    <table border="1">
        <tr>
            <th>Todo Item</th>
            <th>Date Added</th>
            <th>Remove?</th>
            <th>Complete?</th>
        </tr>
    <?php
    //run $resultTodo= again so that it can be reset once changes to the table are made
    $resultTodo = $db->query($fullTodoList);
    if($resultTodo) {
        foreach($resultTodo as $row) { ?>
            <tr>
                <td><?php echo $row["item"]; ?></td>
                <td><?php echo $row["date"]; ?></td>
                <td><input type="checkbox" name="remove-<?php echo $row["id"]; ?>" value="1"></td>
                <td><input type="checkbox" name="complete-<?php echo $row["id"]; ?>" value="1"></td>
            </tr> <?php
        }
    } else {
        echo $db->error;
    }
    ?>
    </table>
</form>

<br>
<br>

<table style="font-family: Arial">
    <tr>
        <th>Todo Item</th>
        <th>Date Completed</th>
    </tr>
<?php

$resultComplete = $db->query($fullCompleteList);
if($resultComplete) {
    foreach($resultComplete as $row) { ?>
        <tr>
            <td style="background-color: grey"><?php echo $row["item"]; ?></td>
            <td style="background-color: darkgrey"><?php echo $row["date"]; ?></td>
        </tr> <?php
    }
} else {
    echo $db->error;
}
?>
</table>