<?php
$config = (isset($config)) ? $config : [];
?>

<div class="row m-0 justify-content-md-center dashboard-kitagil">
    <div  class="mt-4 w-50">
        <?php if (count($config)) { $config = $config[0];
            if( empty($config->last_sync) ){                
            ?>
                <div class="text-center">
                    <h3>Realiza tu primera sincronización</h3>
                    <p>Así de fácil con un solo click todos tus productos se importarán de forma automática conservando, la descripción, foto principal, titulo y sus características. Si quieres saber más <a target="_blank" href="https://help.kitagil.com">click aquí</a></p>
                    <br>
                    <button onclick="syncAllProductsKitagil()" class="btn btn-info">Sincronizar</button>
                    <br>
                    <div class="spinner-border mt-2 d-none" role="status" id="products_sync">
                        <span class="sr-only">Sincronizando productos...</span>
                    </div>
                </div>
            <?php
            }else{
            ?>
                <div class="text-center">
                    <img class="img_plug" src="<?php echo plugin_dir_url(__FILE__) . '../../assets/images/zync.png' ?>" >
                    <br>
                    <h4>Productos sincronizados</h4>
                    <span>Ultima actualización: <?php echo $config->last_sync ?></span>
                    <br>
                    <button style="margin-top:20px" onclick="syncAllProductsKitagil()" class="btn btn-secondary">Sincronizar</button>
                    <br>
                    <div class="spinner-border d-none mt-2" role="status" id="products_sync">
                        <span class="sr-only">Sincronizando productos...</span>
                    </div>
                    <br>
                    <span>¡No hay actualizaciones pendientes!</span>
                </div>
            <?php
            }
        }else{ ?>

        <?php if (!count($config)) { ?>
            <div class="text-center">
                <img class="img_plug"
                    src="<?php echo plugin_dir_url(__FILE__) . '../../assets/images/plug.png' ?>" alt="">

                <div class="mt-2">
                    <b>Sin conexion a Kitagil</b>
                    <br>
                    <span>Ingrese el token y genere la conexion</span>
                </div>
                <br>
                <form id="connect_token_kitagil">
                    <input type="text" class="form-control" placeholder="Token" style="margin-bottom:10px;">
                    <div class="alert alert-primary" id="kitagil_token_error" role="alert" style="display: none;">
                        <strong>El token es invalido</strong>
                    </div>
                    <button type="submit" class="btn btn-secondary">Ingresar</button>
                    <br>
                    <div class="spinner-border mt-2" role="status" id="kitagil_token_loading" style="display: none;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    
                </form>
            </div>
        <?php } }?>
    </div>
</div>