// Copyright (©) 2023 RAMPAGE Interactive
// PayPal API SDK
const PRODUCT_ID = 1;
const PRODUCT_PRICE = 5.00;
const CANCEL_URL = "http://localhost:3000/checkout.html";
const SUCCESS_URL = "http://localhost:3000/checkout.html";
const ppurl = "http://localhost:3000/api";

    window.paypal
        .Buttons({
            style: {
                shape: "rect",
                layout: "vertical",
            },
            async createOrder() {
                try {
                    const response = await fetch(`${ppurl}/orders/create`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            id: PRODUCT_ID
                        }),
                    });

                    const orderData = await response.json();

                    if (orderData.id) {
                        return orderData.id;
                    } else {
                        const errorDetail = orderData?.details?.[0];
                        const errorMessage = errorDetail ?
                            `${errorDetail.issue} ${errorDetail.description} (${orderData.debug_id})` :
                            JSON.stringify(orderData);

                        throw new Error(errorMessage);
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire(`Could not initiate Checkout...<br><br>${error}`);
                }
            },
            async onCancel(data) {
                return window.location = CANCEL_URL;
            },
            async onApprove(data, actions) {
                try {
                    const response = await fetch(`${ppurl}/orders/${data.orderID}/capture`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                    });

                    const orderData = await response.json();
                    // Three cases to handle:
                    //   (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                    //   (2) Other non-recoverable errors -> Show a failure message
                    //   (3) Successful transaction -> Show confirmation or thank you message

                    const errorDetail = orderData?.details?.[0];

                    if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
                        // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                        // recoverable state, per https://developer.paypal.com/docs/checkout/standard/customize/handle-funding-failures/
                        return actions.restart();
                    } else if (errorDetail) {
                        // (2) Other non-recoverable errors -> Show a failure message
                        throw new Error(`${errorDetail.description} (${orderData.debug_id})`);
                    } else if (!orderData.purchase_units) {
                        throw new Error(JSON.stringify(orderData));
                    } else {
                        // (3) Successful transaction -> Show confirmation or thank you message
                        // Or go to another URL:  actions.redirect('thank_you.html');
                        const transaction =
                            orderData?.purchase_units?.[0]?.payments?.captures?.[0] ||
                            orderData?.purchase_units?.[0]?.payments?.authorizations?.[0];
                        Swal.fire(
                            `Transaction ${transaction.status}: ${transaction.id}<br><br>See console for all available details`,
                        );
                        console.log(
                            "Capture result",
                            orderData,
                            JSON.stringify(orderData, null, 2),
                        );

                        if (transaction.status == "COMPLETED") window.location = SUCCESS_URL;
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire(
                        `Sorry, your transaction could not be processed...<br><br>${error}`,
                    );
                }
            },
        })
        .render("#paypal-button-container");
