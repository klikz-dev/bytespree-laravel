<?php $this->load->view("components/head"); ?>
    <div class="dmiux_content dmiux_grid-cont">
        <div class="dmiux_grid-row">
            <div class="dmiux_grid-col">
                <div class="alert alert-danger" role="alert">
                    <?php if (! empty($heading)) : ?>
                        <h4 class="alert-heading"><?php echo $heading; ?></h4>
                        <p><?php echo $message; ?></p>
                    <?php else : ?>
                        <?php echo $message; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('.loader').hide();
    </script>
<?php $this->load->view("components/foot");?>
