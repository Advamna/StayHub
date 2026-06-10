<?php
require_once 'config.php';

// Add status column to listings
$sql1 = "IF NOT EXISTS(SELECT 1 FROM sys.columns WHERE Name = N'status' AND Object_ID = Object_ID(N'listings'))
BEGIN
    ALTER TABLE listings ADD status VARCHAR(50) DEFAULT 'active' NOT NULL;
END";
$stmt1 = sqlsrv_query($conn, $sql1);
if ($stmt1 === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Create notifications table
$sql2 = "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[notifications]') AND type in (N'U'))
BEGIN
    CREATE TABLE notifications (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT NOT NULL,
        title NVARCHAR(200),
        message NVARCHAR(MAX) NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
END";
$stmt2 = sqlsrv_query($conn, $sql2);
if ($stmt2 === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Database updated successfully.";
?>


// Add title column to notifications if it was created without it
$sql3 = "IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE Name = N'title' AND Object_ID = Object_ID(N'notifications'))
BEGIN
    ALTER TABLE notifications ADD title NVARCHAR(200) NULL;
END";
$stmt3 = sqlsrv_query($conn, $sql3);
if ($stmt3 === false) {
    echo "Warning: could not add title column to notifications: ";
    print_r(sqlsrv_errors());
} else {
    echo " notifications.title column OK.\n";
}
