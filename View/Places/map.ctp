    <div class="filter_topbar">
            <?php echo $this->element('sidebar/topbar_header', array('category_name' => $category_name)); ?>
    </div>
    <div class="list">
            <?php echo $this->Map->places_markers(array('style' => 'width:413px;height:500px;float: left;')); ?>
    </div>
