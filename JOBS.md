# Mejoras

1.⁠ ⁠Actualización en tiempo real del estatus de pedido
2.⁠ ⁠Alarma de pedidos nuevos en el portal de pedidos del administrador
3.⁠ ⁠Cambio de estatus del pago en el pedido cuando se manda a imprimir (en web sale pagado, pero al mandar a imprimir sigue pendiente)
4. Adicionar las vistas de los clientes con sus pedidos en el admin del plugin.
5. Adiconar en el shortcode [mydelivery-orders] un boton al lado de imprimir pedido, que se llame imprimir comanda, que sea igual que imprimir pedido, pero con los siguientes datos: 
    Orden: (Numero de Orden)
    01/07 - 14:48 (Dia y hora del pedido)
    Alejandor (Nombre del Cliente)
    (0412)233-4334 (Telefono del Cliente)
    5 x Producto 1 (Cantidad del producto)
6. Adiconar en el shortcode [mydelivery-orders]  un bonton entre confirmar y listo un status del pedido que tenga el nombre de "En Proceso"
7. En la Pagina Informes del administrador del plugin, donde funcione los filtros, quisiera tener estos reportes:
    * Pedidos por platos (tabla con nombre del producto, cantidad de pedidos pagados, total)
    * Pedidos por platos segun la semana Lunes a Domingo (tabla con nombre del producto, cantidad de pedidos pagados, total)
    * Pedido por Modo de funcionamiento	(tabla con Modo de funcionamiento, cantidad de pedidos pagados, total)
    * Pedido por Tipo de Pago (tabla con Tipo de Pago, cantidad de pedidos pagados, total)

8. Podemos adicionar en el setting del proyecto un checkox para hacer la conversion en dolares a bolivares usando la siguiente API https://ve.dolarapi.com/v1/dolares el payload es el ssiguiente
````
[{"fuente": "oficial","nombre": "Oficial","compra": null,"venta": null,"promedio": 136.8931,"fechaActualizacion": "2025-08-17T15:01:59.094Z"},{"fuente": "paralelo","nombre": "Paralelo","compra": null,"venta": null,"promedio": 193.41,"fechaActualizacion": "2025-08-17T15:02:02.723Z"},{"fuente": "bitcoin","nombre": "Bitcoin","compra": null,"venta": null,"promedio": 115.17,"fechaActualizacion": "2025-08-17T15:01:59.094Z"}]
````
donde tomaremos la fuente oficial. y en el producto crear un campo abajo para mostrar la conversion.


## Bug
1. Revisa como muestras los valores de dinero: usar (.) ponto para las milesimas y (,) para los decimales
2. Cuando cambio de estatus el pedido a confirmado en el shortcode [mydelivery-orders], y quiero imprimir el pedido el estatus de pagamento no cambia, solo al momento que hago refresh en el navegador