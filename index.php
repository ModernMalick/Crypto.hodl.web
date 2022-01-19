<?php

$servername = "localhost";
$username = "root";
$password = "";
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
mysqli_select_db($conn, 'portfolio');

if (isset($_POST['delete'])) {
    $query = "DELETE FROM asset WHERE `ticker`= '" . $_POST['delete'] . "'";
    if (mysqli_query($conn, $query)) {
        header("Refresh:0");
    }
}

if (isset($_POST['update'])) {
    $total = count($_POST['ticker']);
    $ticker_arr = $_POST['ticker'];
    $invested_arr = $_POST['invested'];
    $current_arr = $_POST['current'];
    for ($i = 0; $i < $total; $i++) {
        $ticker = $ticker_arr[$i];
        $invested = $invested_arr[$i];
        $current = $current_arr[$i];
        $query = $conn->prepare("UPDATE asset SET `invested`= ?, `current`= ? WHERE `ticker`= ?");
        $query->bind_param("sss", $invested, $current, $ticker);
        $query->execute();
    }
}

if (isset($_POST["new_ticker"]) && isset($_POST["new_invested"])) {
    $query = $conn->prepare("INSERT INTO `asset` (`id`, `ticker`, `invested`, `current`) VALUES (NULL,?,?,?)");
    $query->bind_param("sss", $_POST["new_ticker"], $_POST["new_invested"], $_POST["new_invested"]);
    $query->execute();
}

$query_getTotals = "SELECT SUM(invested), SUM(current)  FROM asset";
$result_getTotals = mysqli_query($conn, $query_getTotals);
$totals = array();
$totalSentiment = "";
$sentimentIcon = "";
while ($row = mysqli_fetch_array($result_getTotals)) {
    $totals[] = $row;
    if ($row['SUM(current)'] < $row['SUM(invested)']) {
        $totalSentiment = "negative";
        $sentimentIcon = "assets/sentiments/explosion.png";
    } else {
        $totalSentiment = "positive";
        $sentimentIcon = "assets/sentiments/rocket.png";
    }
}

$query_getAssets = "SELECT * FROM asset ORDER BY ticker ASC";
$result_getAssets = mysqli_query($conn, $query_getAssets);
$tableRows = array();
$tableSentiments = array();
while ($row = mysqli_fetch_array($result_getAssets)) {
    $tableRows[] = $row;
}

$query_getChart = "SELECT * FROM asset ORDER BY current ASC";
$result_getChart = mysqli_query($conn, $query_getChart);
$chartData = array();
while ($row = mysqli_fetch_array($result_getChart)) {
    $chartData[] = [$row['ticker'], $row['invested'], $row['current']];
}
$dataArr = array();
foreach ($chartData as $i) {
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

mysqli_close($conn);
?>
<!doctype html>
<html lang="en">
<head>
    <title>CryptoWatcher</title>
    <link rel="icon" type="image/x-icon" href="assets/bitcoin.ico">
    <link rel="stylesheet" media="screen" type="text/css" title="style" href="index.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type='text/javascript'>
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawTitleSubtitle);

        function drawTitleSubtitle() {
            let data = new google.visualization.DataTable(<?php echo json_encode($arr)?>);
            let options = {
                colors: [
                    '#acc3e5',
                    '#4798F4'
                ],
                legend: {
                    position: 'none'
                }
            };
            let materialChart = new google.charts.Bar(document.getElementById('chart'));
            materialChart.draw(data, options);
        }
    </script>
</head>
<body class="no_margin column">
<div class="main_container row separate">
    <div class="column">
        <h1 class="separate">Welcome to your portfolio !</h1>
        <div class="row">
            <?php foreach ($totals as $total): ?>
                <div class="column separate">
                    <h2 class="no_margin">Current value :&nbsp;</h2>
                    <h3 class="no_margin">Invested :&nbsp;</h3>
                    <h3 class="no_margin">Gains :&nbsp;</h3>
                </div>
                <div class="column align_right separate">
                    <h2 class="<?php echo $totalSentiment ?> no_margin"><?php echo abs($total['SUM(current)']) ?>
                        €</h2>
                    <h3 class="no_margin"><?php echo $total['SUM(invested)'] ?> €</h3>
                    <h3 class="<?php echo $totalSentiment ?> no_margin"><?php echo abs(round(((($total['SUM(current)'] - $total['SUM(invested)']) * 100) /  $total['SUM(invested)']), 0)) ?> % (<?php echo abs($total['SUM(current)'] - $total['SUM(invested)']) ?>
                        €)</h3>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <img src="<?php echo $sentimentIcon ?>" class="separate icon" alt="sentiment">
</div>
<div class="main_container row separate fill_height">
    <form action="index.php" method="post" class="column glass_panel separate fill_height">
        <table class="fill_height column">
            <thead>
            <tr class="row">
                <th class="ticker">Ticker</th>
                <th>Invested</th>
                <th>Current</th>
                <th class="gain">Gain</th>
                <th></th>
            </tr>
            </thead>
            <tbody class="fill_height">
            <?php foreach ($tableRows as $tableRow): ?>
                <tr class="row">
                    <td>
                        <input class="ticker" id="ticker" type="text" name="ticker[]"
                               value="<?php echo $tableRow['ticker'] ?>"/>
                    </td>
                    <td><input type="number" name="invested[]" value="<?php echo $tableRow['invested'] ?>"/></td>
                    <td><input type="number" name="current[]" value="<?php echo $tableRow['current'] ?>"/></td>
                    <?php
                    $sentiment = "";
                    $sign = "";
                    if ($tableRow['current'] < $tableRow['invested']) {
                        $sentiment = "negative";
                    } else {
                        $sentiment = "positive";
                        $sign = "+";
                    }
                    ?>
                    <td class="<?php echo $sentiment ?> gain"><?php echo $sign ?><?php echo $tableRow['current'] - $tableRow['invested'] ?>
                        €
                    </td>
                    <td class="delete_container row">
                        <button class="delete" type="submit" name="delete" value="<?php echo $tableRow['ticker'] ?>">
                            <img src="assets/close.png">
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button class="save separate" type="submit" name="update">SAVE</button>
    </form>
    <div class="column fill_height">
        <div class="separate glass_panel  fill_height row">
            <div id="chart" class="fill_height separate"></div>
        </div>
        <div class="row fill_height">
            <form class="glass_panel fill_height separate column" action="index.php" method="post" id="newForm">
                <h2 class="separate">New Investment</h2>
                <div class="row full_row">
                    <label class="separate" for="ticker">Ticker</label>
                    <input class="separate" type="text" name="new_ticker" placeholder="$HODL">
                </div>
                <div class="row full_row">
                    <label class="separate" for="invested">Invested</label>
                    <input class="separate" type="number" name="new_invested" placeholder="0">
                </div>
                <button class="save separate" type="submit">ADD</button>
            </form>
            <div class="column fill_height">
                <div class="row fill_height">
                    <div class="separate glass_panel column fill_height end">
                        <h2 class="separate">Communities</h2>
                        <a class="separate" href="https://www.reddit.com/r/Crypto_com/" target="_blank">r/Crypto_Com</a>
                        <a class="separate" href="https://www.reddit.com/r/CryptoCurrency/" target="_blank">r/CryptoCurrency</a>
                        <a class="separate" href="https://www.reddit.com/r/CryptoCurrencyFire/" target="_blank">r/CryptoCurrencyFire</a>
                    </div>
                    <div class="separate glass_panel column fill_height end">
                        <h2 class="separate">Misc.</h2>
                        <a class="separate" href="https://coinmarketcap.com/currencies/presearch/" target="_blank">Pre
                            to USD</a>
                        <a class="separate" href="https://cryptoroyale.one/" target="_blank">Crypto Royale</a>
                        <a class="separate" href="https://www.reddit.com/r/CryptoCurrencyFire/" target="_blank">Moons to
                            USD</a>
                    </div>
                </div>
                <p id="signature" class="separate">© Copyright 2021 Malick Ndiaye</p>
            </div>
        </div>
    </div>
</div>
<footer>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</footer>
</body>
</html>

