<?php
/**
 * @var $arParams
 */
?>
<script>
    // формирование массива заказа, менять не стоит
    document.querySelector(".persons-container").addEventListener("click", async (event) => {
        let target = event.target.closest(".submit_custom_order");
        if (!target) return;

        event.preventDefault();
        inputs = document.querySelectorAll(".form_order.active input");
        let data = new URLSearchParams()
        for (let i = 0; i < inputs.length; i++) {
            if (inputs[i].checked) data.set(inputs[i].name, inputs[i].id.replace(/[^0-9]/g, ""));
            if (inputs[i].placeholder && inputs[i].value) data.set(inputs[i].id.replace(/[^0-9]/g, ""), inputs[i].value);
        }
        data.set("PERSONTYPEID", document.querySelector(".form_order.active .person").id.replace(/[^0-9]/g, ""))
        let curDir = document.location.protocol + '//' + document.location.host + document.location.pathname;
        // activate
        data.set("MAKE_ORDER", "Y");
        //activate
        console.log(data)
        await fetch(curDir, {
            method: 'POST',
            body: data,
        })
    })

    // переключение между плательщиками
    document.querySelector(".tabs-container").addEventListener('click', function (event) {
        const target = event.target.closest('.tab-button');
        if (!target) return;

        let id = event.target.id.replace(/[^0-9]/g, "");
        let forms = document.querySelectorAll(".form_order");
        for(let i = 0; i < forms.length; i++){
            forms[i].style = "display: none";
            forms[i].classList.remove("active");
        }
        document.querySelector(`#form_${id}`).style = "display: block";
        document.querySelector(`#form_${id}`).classList.add("active");
    });
</script>
