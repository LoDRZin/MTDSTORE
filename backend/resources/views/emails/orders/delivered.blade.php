<x-mail::message>
# Olá, {{ $order->customer->name ?? 'Cliente' }}!

Seu pagamento foi confirmado e seu pedido **#{{ $order->uuid }}** está pronto para ser resgatado.

Clique no botão abaixo para visualizar as chaves dos produtos digitais que você adquiriu. 
**Importante:** Este link expira em 48 horas por motivos de segurança.

<x-mail::button :url="$url">
Resgatar Meus Produtos
</x-mail::button>

Caso o botão não funcione, você pode acessar nossa página de resgate e inserir o número do seu pedido e e-mail.

Obrigado por comprar conosco,<br>
{{ config('app.name') }}
</x-mail::message>
