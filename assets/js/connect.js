jQuery(document).ready(function ($) {
    let url_kitagil = "http://localhost:3000/api/1.0/hooks"


    jQuery('form#connect_token_kitagil').submit(async (e) => {
        e.preventDefault();
        const inputField = $('input').val();
        jQuery('#kitagil_token_error').fadeOut('fast')
        if (!inputField.length) {
            alert('Debes ingresar el token')
            return
        }
        jQuery('#kitagil_token_loading').fadeIn()
        const validToken = await tryTokenKitagil(inputField).then(info => {
            jQuery('#kitagil_token_loading').fadeOut()
            return true
        }).catch(error => {
            jQuery('#kitagil_token_loading').fadeOut(100)
            jQuery('#kitagil_token_error').fadeIn('slow')
            return false
        });
        (validToken) ? await saveTokenApiKitagil(inputField) : null;
    });

    // Boton para sincronizar todos los productos (Solo llamar cuando es la primera sincronizacion)
    jQuery('#sync_all_products').click(async (e) => {
        e.preventDefault();
        await syncAllProducts();
    });


    // Function
    const tryTokenKitagil = async (token = '') => new Promise(async (resolve, reject) => {
        console.log('Estamos obteniendo el token')
        jQuery.ajax({
            type: "get",
            url: `${url_kitagil}/token`,
            headers: {
                'keyMachine': `${token}`
            },
            success: function (response) {
                console.log(response)
                resolve(response)
            },
            error: function (e) {
                reject({
                    status: e.status, responseText: e.responseText
                })
                // log error in browser
                console.log(e);
            }
        });
    });

    const saveTokenApiKitagil = async (token = '') => new Promise(async (resolve, reject) => {
        console.log('Estamos haciendo la peticion post')
        jQuery.ajax({
            type: "post",
            url: `${ajaxurl}`,
            data: { token_api: token, action: 'save_token_api' },
            success: function (response) {
                console.log(response)
                window.location.reload()
                resolve(response)
            },
            error: function (e) {
                reject({
                    status: e.status, responseText: e.responseText
                })
                // log error in browser
                console.log(e);
            }
        });
    });
});


const syncAllProductsKitagil = async (token) => {
    jQuery('#products_sync').fadeIn();
    jQuery.ajax({
        type: "post",
        url: `${ajaxurl}`,
        data: { action: 'sync_all_products_kitalig' },
        success: function (response) {
            if (JSON.stringify(response).includes('error')) {
                alert('Ups ocurrio un error!')
            } else {
                // window.location.reload()
            }
        },
        error: function (e) {
            alert('Ups ocurrio un error!')
        }
    });
}