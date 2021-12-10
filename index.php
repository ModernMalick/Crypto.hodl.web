<!DOCTYPE html ><html lang="en">
    <head>
        <title>Portfolio</title>
        <link rel="icon" type="image/x-icon" href="assets/profits.ico">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <link rel="stylesheet" media="screen" type="text/css" title="style" href="index.css"/>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400&display=swap" rel="stylesheet">

        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    </head>

    <body>
        <div>
            <h1>Welcome to your portfolio</h1>
            <form action="index.php" method="post">
                <?php
                    $servername = "localhost";
                    $username = "root";
                    $password = "";

                    $conn = new mysqli($servername, $username, $password);

                    if($conn->connect_error){
                        die("Connection failed: " . $conn->connect_error);
                    }

                    mysqli_select_db($conn, 'portfolio');
                    $query_getAssets = "SELECT * FROM asset ORDER BY ticker ASC";
                    $query_getTotals = "SELECT SUM(invested), SUM(current)  FROM asset";
                    $result_getAssets = mysqli_query($conn, $query_getAssets);
                    $result_getTotals = mysqli_query($conn, $query_getTotals);

                    $query_getAssetsTable = "SELECT * FROM asset ORDER BY current ASC";
                    $result_getAssetsTable = mysqli_query($conn, $query_getAssetsTable);

                    if(isset($_POST["ticker"]) && isset($_POST["invested"]) && isset($_POST["type"])){
                        $query_insertAsset = "INSERT INTO `asset` (`id`, `ticker`, `invested`, `current`) VALUES (NULL,'" . $_POST["ticker"] . "'," . $_POST["invested"] . "," . $_POST["invested"] . ")";
                        if (mysqli_query($conn, $query_insertAsset)) {
                            header("Refresh:0");
                        } else {
                            echo "";
                        }
                    }

                    if(isset($_POST['update'])){ 
                        $total = count($_POST['ticker']); 
                        $ticker_arr = $_POST['ticker']; 
                        $invested_arr = $_POST['invested']; 
                        $current_arr = $_POST['current']; 
                        for($i = 0; $i < $total; $i++){ 
                           $ticker = $ticker_arr[$i]; 
                           $invested = $invested_arr[$i]; 
                           $current = $current_arr[$i]; 
                           $query = "UPDATE asset SET `invested`= '".$invested."', `current`= '".$current."' WHERE `ticker`= '".$ticker."'"; 
                           if (mysqli_query($conn, $query)) {
                            header("Refresh:0");
                            } else {
                                echo "";
                            }
                        } 
                    }

                    if(isset($_POST['delete'])){ 
                        $query = "DELETE FROM asset WHERE `ticker`= '".$_POST['tickerID']."'"; 
                        if (mysqli_query($conn, $query)) {
                            header("Refresh:0");
                        } else {
                             echo "";
                        }
                    }

                    while($row = mysqli_fetch_array($result_getTotals)){
                        $class = "";
                        if($row['SUM(current)'] < $row['SUM(invested)']){
                            $class = "negative";
                        }
                        else{
                            $class = "positive";
                        }
                        echo "<div>
                        <span>
                            <h2>Current value:</h2>
                            <h2 class='$class live_values'>" . $row['SUM(current)'] . "€</h2>
                        </span>
                        <span>
                            <h3>Invested:</h3>
                            <h3 class='live_values'>" . $row['SUM(invested)'] . "€</h3>
                        </span>
                        <span>
                            <h3>Gains:</h3>
                            <h3 class='$class live_values'>" . ($row['SUM(current)'] - $row['SUM(invested)']). "€</h3>
                        </span>
                            </div>";
                    }

                    echo "<div id='formUpdate'><table>
                            <thead>
                                <tr>
                                    <th>
                                        Ticker
                                    </th>
                                    <th>
                                        Invested
                                    </th>
                                    <th>
                                        Current
                                    </th>
                                    <th>
                                        Gain
                                    </th>
                                    <th>
                                        Delete
                                    </th>
                                </tr>
                            </thead>
                            <tbody>";

                    $liveData = array();

                    while($row = mysqli_fetch_array($result_getAssets)){   
                        $class = "";
                        if($row['current'] < $row['invested']){
                            $class = "negative";
                        }
                        else{
                            $class = "positive";
                        }
                        echo "<tr><td><input type='text' name='ticker[]' value='".$row['ticker']."' readonly class='readonly'/></td><td><input type='number' name='invested[]' value='".$row['invested']."'/></td><td><input type='number' name='current[]' value='".$row['current']."'/></td><td class=$class>" . ($row['current'] - $row['invested']) . "€</td><form action='index.php' method='post'><td class='hidden'><input type='text' name='tickerID' value='".$row['ticker']."'/></td>
                        <td><button type='submit' name='delete'>DELETE</button></td></form></tr>";
                    }

                    while($row = mysqli_fetch_array($result_getAssetsTable)){   
                        $GLOBALS['liveData'][] = [$row['ticker'], $row['invested'], $row['current']];
                    }

                    echo "</tbody></table><div id='updateContainer'><button type='submit' name='update'>UPDATE</button></div></div>";

                    $dataArr = array();

                    foreach($liveData as $i){
                        $dataArr[] = array(
                            "c" => array(
                                array(
                                    "v" => $i[0],
                                    "f" => null
                                ),
                                array(
                                    "v" => $i[1],
                                    "f" => null
                                ),
                                array(
                                    "v" => $i[2],
                                    "f" => null
                                )
                            ),
                        );
                    }

                    $arr = array(
                        "cols" => array( 
                            array(
                                "id" => "",
                                "label" => "",
                                "pattern" => "",
                                "type" => "string"
                            ),
                            array(
                                "id" => "",
                                "label" => "Invested",
                                "pattern" => "",
                                "type" => "number"
                            ),
                            array(
                                "id" => "",
                                "label" => "Worth",
                                "pattern" => "",
                                "type" => "number"
                            )
                        ),
                        "rows" => $dataArr
                    );

                    echo
                    "<script type='text/javascript'>
                        google.charts.load('current', {packages: ['corechart', 'bar']});
                        google.charts.setOnLoadCallback(drawTitleSubtitle);
                        
                        function drawTitleSubtitle() {
                            var data = new google.visualization.DataTable(".json_encode($arr).");

                            var options = {
                                colors: [
                                    '#285CAF',
                                    '#820911'
                                ],
                                chartArea: {
                                    width: '50%',
                                    height: '50%'
                                },
                                legend: {
                                    position: 'none'
                                }
                        };
                    
                          var materialChart = new google.charts.Bar(document.getElementById('colchart'));
                          materialChart.draw(data, options);
                        }
                    </script>";

                    mysqli_close($conn);
                ?>
            </form>
        </div>
    
        <div id="rightDiv">
            <div id="colchartContainer"><div id="colchart"></div></div>
            <div id="bottomRightDiv">
                <form action="index.php" method="post" id="formAdd">
                        <h4 id="formTitle">New investment</h4>

                        <span class="formSpan">
                            <label for="ticker">Ticker</label>
                            <input type="text" id="ticker" name="ticker">
                        </span>

                        <span class="formSpan">
                            <label for="invested">Invested</label>
                            <input type="number" id="invested" name="invested">
                        </span>

                        <span class="formSpan">
                            <label for="type">Type</label>
                            <select name="type" id="type">
                                <option value="crypto">Crypto</option>
                                <option value="stock">Stock</option>
                            </select>
                        </span>

                        <button type="submit">ADD TO PORTFOLIO</button>
                </form>
            </div>
        </div>
    </body>
    <footer>
        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>
    </footer>
</html>
