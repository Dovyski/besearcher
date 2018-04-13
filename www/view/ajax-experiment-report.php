<?php
    $aData = Besearcher\View::data();
    $aFilter = $aData['filter'];
    $aEntries = $aData['entries'];
?>

<table width="100%" class="table-bordered">
    <tr>
        <?php foreach($aEntries as $aNum => $aEntry) { ?>
            <td style="width: 400px;">
                <table style="width: 400px;" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50%">Name</th>
                            <th style="width: 50%">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if(count($aEntry['meta']) > 0) {
                                foreach($aEntry['meta'] as $aItem) {
                                    echo '<tr>';
                                        echo '<td><code><i class="fa fa-tag"></i> '.$aItem['name'].'</code></td>';
                                        echo '<td>'.print_r($aItem['data'], true).'</td>';
                                    echo '</tr>';
                                }
                            }
                        ?>
                        <tr><td colspan="2"></td></tr>
                        <?php
                            if(count($aEntry['params']) > 0) {
                                foreach($aEntry['params'] as $aName => $aValue) {
                                    echo '<tr>';
                                        echo '<td><code><i class="fa fa-sliders"></i> '.$aName.'</code></td>';
                                        echo '<td>'.str_replace(',', ', ', $aValue).'</td>';
                                    echo '</tr>';
                                }
                            }

                            echo '<tr>';
                                echo '<td><code><i class="fa fa-link"></i> Hashes</code></td>';
                                echo '<td>'.Besearcher\View::createResultLink($aEntry['result']['experiment_hash'], $aEntry['result']['permutation_hash']).'</td>';
                            echo '</tr>';
                        ?>
                    </tbody>
                </table>
            </td>
        <?php } ?>
    </tr>
</table>
