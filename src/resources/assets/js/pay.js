/*
    Created by: Azizbek Eshonaliyev
    date: 2/26/2019 13:41
 */

$(document).ready(function () {

    /*
        Create Payment system.
     */

    let counter_fields = 0;
    let add_param_btn = document.getElementById("addPaymentSystemParamBtn");
    let fields_list = $('#fieldsList');

    function create_payment_system_field() {
        counter_fields++;
        return `
                 <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label for="recipient-label-${counter_fields}" class="col-form-label">Label name:</label>
                            <input name="params[new-${counter_fields}][label]" type="text" class="form-control" id="recipient-label-${counter_fields}"  value="">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="recipient-name-${counter_fields}" class="col-form-label">Key:</label>
                            <input name="params[new-${counter_fields}][name]" type="text" class="form-control" id="recipient-name-${counter_fields}"  value="">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="recipient-value-${counter_fields}" class="col-form-label">Value:</label>
                            <input name="params[new-${counter_fields}][value]" type="text" class="form-control" id="recipient-value-${counter_fields}"  value="">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <button class="btn btn-outline-danger remove_param_btn_create_page remove_payment_system_btn_sb"> <span class="fa fa-trash"></span> Delete</button>
                        </div>
                    </div>
                </div>
        `;
    }

    if (add_param_btn)
        add_param_btn.addEventListener('click',function (event) {
           event.preventDefault();
           fields_list.append(create_payment_system_field());
        });

    fields_list.on('click','.remove_param_btn_create_page',function (event) {
        event.preventDefault();
        $(this).parent().parent().parent().remove();
    });
});
