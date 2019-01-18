<?php

session_start();

function show_error($error)
{
    if (isset($_SESSION['errors'][$error]))
        echo "<span class='error'>{$_SESSION['errors'][$error]}</span>";
}

if (isset($_POST['solve'])) {
    unset($_SESSION['errors']);
    require 'Transport.php';
    $manufacturers = $_POST['manufacturer-values'];
    $consumers = $_POST['consumer-values'];
    $transport = Transport::create_transport($_POST['manufacturers'], $_POST['consumers'],
        $manufacturers, $consumers, $_POST['transport']);
}
?>
<!doctype html>
<html lang="en">
<head>
    <title>Transport Task</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Solving an open/balanced transport task by building an initial support plan with the minimum element method or northwest corner . Решаване на отворена/затворена транспортна задача, чрез построяване на начален опорен план с правилото на минималния елемент или правилото на северозападния ъгъл.">
    <meta name="keywords" content="Transportation, Problem, Transport, Task, Northwest, Corner, Minimum, Element, Open, Balanced, Method, Potencials, Транспортна, Задача, Северозападен, Ъгъл, Минимален, Елемент, Отворен, Отворена, Затворен, Затворена, Метод, Потенциали">
    <meta name="author" content="Jivko Jelev">
    <link rel="icon" href="iconfinder_equipment_2318444.png" type="image/png" sizes="32x32">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
          crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
          integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <meta name ="robots" content="index">
</head>
<body>
<div class="content">
    <div class="col-md-10 col-md-offset-1">
        <div class="col-md-4 col-md-offset-4">
            <form class="form-horizontal" method="post">
                <div class="form-group">
                    <label for="manufacturers" class="col-sm-6 control-label">Number of Producers</label>
                    <div class="col-sm-2">
                        <input type="number" id="manufacturers" name="manufacturers" class="form-control"
                               value="<?php echo(isset($_POST['manufacturers']) ? $_POST['manufacturers'] : 3) ?>"
                               autocomplete="off" min="2">
                    </div>
                </div>
                <div class="form-group">
                    <label for="consumers" class="col-sm-6 control-label">Number of Consumers</label>
                    <div class="col-sm-2">
                        <input type="number" id="consumers" name="consumers" class="form-control"
                               value="<?php echo (isset($_POST['consumers'])) ? $_POST['consumers'] : 2 ?>"
                               autocomplete="off" min="2">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0px;">
                    <button class="btn btn-primary btn-block" type="button" id="apply" name="apply">Apply</button>
                </div>
                <div class="form-group">
                    <div id="transport-table">
                        <?php
                        if (isset($_POST['solve'])){
                        ?>
                        <hr class="first-hr">
                        <div class="form-group radio-method">
                            <fieldset><legend>Method</legend>
                                <label class="radio-inline">
                                          <input type="radio" name="method" value="minimum" <?php if(isset($_POST['method']) && $_POST['method']=='minimum'){ echo 'checked';}?>>Minimum Cost
                                        </label>
                                    <label class="radio-inline">
                                          <input type="radio" name="method" value="northwest" <?php if(isset($_POST['method']) && $_POST['method']=='northwest'){ echo 'checked';}?>>Northwest Corner
                                        </label>
                                </fieldset>
                            </div>

                        <div class="form-group">
                            <label for="manufacturer-values" class="col-sm-2 control-label">Producers</label>
                            <div class="col-sm-10">
                                <input type="text" id="manufacturer-values" name="manufacturer-values"
                                       class="form-control"
                                       value="<?php echo(isset($_POST['manufacturer-values']) ? implode(',', $manufacturers) : '') ?>"
                                       required><?php
                                show_error('manufacturers-cells'); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="consumer-values" class="col-sm-2 control-label">Consumers</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="consumer-values" name="consumer-values"
                                       value="<?php echo(isset($_POST['consumer-values']) ? implode(',', $consumers) : '') ?>"
                                       required><?php
                                show_error('consumers-cells'); ?>
                            </div>
                        </div>
                        <p class="legend">Separates each producer and consumer with comma - "220,310,50"</p>
                        <h3>Matrix of Transport Costs</h3>
                        <table class="table"><?php
                            for ($i = 0; $i < count($_POST['transport']); $i++) {
                                echo '<tr>';
                                for ($j = 0; $j < count($_POST['transport'][0]); $j++) {
                                    echo '<td><input type="number" name="transport[' . $i . '][' . $j . ']" class="form-control col-sm-1" required min="1" value="' . (isset($_POST['transport'][$i][$j]) ? $_POST['transport'][$i][$j] : '') . '" autocomplete="off"></td>';
                                }
                                echo '</tr>';
                            }
                            echo '</table><button class="btn btn-primary btn-block" type="submit" id="solve" name="solve">Solve</button>';
                            echo '<hr class="first-hr">';
                        } ?>
                            <?php
                            if(isset($_POST['solve'])) {
                            }
                            if (isset($transport)) {
                                echo "<span>Task type: </span><span class='blue-text'>" . $transport->get_type() . '</span><br>';
                                if($_POST['method']=='minimum') {
                                    $transport->solve_with_minimum_cost();
                                }else{
                                    $transport->solve_with_northwest_corner();
                                }
                                show_error('general');
                            } else {
                                show_error('transport costs');
                                show_error('global');
                            }
                            ?>
                    </div>
                </div>
            </form>
            <div class="before-footer"></div>
        </div>
    </div>
</div>
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
        crossorigin="anonymous"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
        crossorigin="anonymous"></script>
<script src="internal.js"></script>
</body>
</html>
<?php
    unset($_SESSION['errors']);
