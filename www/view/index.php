<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Welcome!</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <?php if(!$aData['loaded']) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger" role="alert"><strong>Unable to load data!</strong> Check the <em>config.ini</em> file and make sure <em>besearcher_ini_file</em> contains the right path to the INI file being used by Besearcher.</div>
            </div>
        </div>
    <?php } else { ?>

    <div class="row">
        <div class="col-lg-12">
            <p>Besearcher is configured and working as expected. Below are the configuration settings being used.</p>
            <p>Configuration file: <code><?php echo $aData['ini_path']; ?></code></p>

            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Directive</th>
                        <th>Value</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($aData['ini'] as $aName => $aValue) {
                            if($aName != 'task_cmd_params') {
                                echo '<tr>';
                                    echo '<td>'.$aName.'</td>';
                                    echo '<td>'.(is_array($aValue) ? '<pre>'.print_r($aValue, true).'</pre>' : $aValue).'</td>';
                                    echo '<td></td>';
                                echo '</tr>';
                            }
                        }

                        echo '<tr><td colspan="3" style="text-align: center; font-weight: bold;">Task params</td></tr>';

                        foreach($aData['task_params'] as $aName => $aValue) {
                            echo '<tr>';
                                echo '<td>'.$aName.'</td>';
                                echo '<td>'.(is_array($aValue) ? '<pre>'.print_r($aValue, true).'</pre>' : $aValue).'</td>';
                                echo '<td></td>';
                            echo '</tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
