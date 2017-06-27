<?php
    $aData = Besearcher\View::data();
    $aTasks = $aData['tasks'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Tasks</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    List of existing tasks
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <table width="100%" class="table table-striped table-bordered table-hover" id="dataTables-example">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Id</th>
                                <th style="width: 50%;">Name</th>
                                <th>Type</th>
                                <th>Modified</th>
                                <th>Created</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $aNum = 0;
                                foreach($aTasks as $aRow) {
                                    echo '<tr class="'.($aNum++ % 2 == 0 ? 'even' : 'odd').'">';
                                        echo '<td>';
                                            echo '<a href="entry.php?id='.$aRow->id.'"><i class="fa fa-edit"></i></a> &bull; ';
                                            echo '<a href="entry.php?id='.$aRow->id.'&delete=1" onclick="return confirm(\'Delete?\')"><i class="fa fa-trash"></i></a>';
                                        echo '</td>';
                                        echo '<td>'.$aRow->id.'</td>';
                                        echo '<td>'.$aRow->name.'</td>';
                                        echo '<td>'.$aRow->type.'</td>';
                                        echo '<td>'.date('Y-m-d H:i:s', $aRow->modified).'</td>';
                                        echo '<td>'.date('Y-m-d H:i:s', $aRow->created).'</td>';
                                        echo '<td>'.$aRow->active.'</td>';
                                    echo '</tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                    <!-- /.table-responsive -->
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
