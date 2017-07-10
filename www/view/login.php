<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <img src="img/logo/logo-text.png" title="Besearcher logo" style="width: auto; height: 50px;"/>
                    </div>
                    <div class="panel-body">
                        <?php if(!empty($aData['error'])) { ?>
                            <div class="alert alert-danger" role="alert"><strong>Oops!</strong> <?php echo $aData['error']; ?></div>
                        <?php } ?>
                        <form role="form" action="login.php" method="post">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Username" name="user" type="text" autofocus>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="password" type="password" value="">
                                </div>
                                <!-- Change this to a button or input when using this as a form -->
                                <button type="submit" class="btn btn-lg btn-info btn-block">Login</a>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
