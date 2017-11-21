<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <img src="img/logo/logo-text.png" title="Besearcher logo" style="width: 100%; height: auto;"/>
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
        <div class="row">
            <div class="col-lg-12">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading"><strong>Manage users</strong></div>
                    <div class="panel-body">
                        You have to manage users via <em>command line</em>. To add a new user, run the command <code>php BESEARCHER_HOME\cmd\bcuser.php --ini=INI_PATH --add</code>, where <code>BESEARCHER_HOME</code> is the folder where besearcher is installed, e.g. <code>c:\besearcher\</code>, and <code>INI_PATH</code> is the path to your configuration INI file, e.g. <code>c:\besearcher\config.ini</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
