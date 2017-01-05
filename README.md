# Quack_Cielo
Integração de pagamento Cielo para Magento 1.x

Esta extensão utiliza a biblioteca [Tritoq/Payment](https://github.com/nezkal/Cielo), e distribui os arquivos da biblioteca, devido a incompatibilidade do Magento 1.x com o uso de namespaces. Agradecimentos ao Artur, que disponibilizou a biblioteca.


## Compatibilidade
Magento 1.7, 1.8 e 1.9.x


## Controle e Transparência
Os detalhes da transação ficam mais protegidos em telas separadas, onde é possível gerenciar o acesso.

### A tela do pedido exibe as informações básicas da transação:
![image](https://cloud.githubusercontent.com/assets/13813964/21666264/ba7a6fa0-d2d7-11e6-8c19-209fa98806d8.png)

### Os detalhes da transação ficam registrados na aba _Transações_:
![image](https://cloud.githubusercontent.com/assets/13813964/21666169/3967a6ee-d2d7-11e6-8062-d21a006241aa.png)

### Ao clicar sobre a transação é possível ver os detalhes:
![image](https://cloud.githubusercontent.com/assets/13813964/21666567/9f3b8d9e-d2d9-11e6-8ea5-743528386b62.png)

## Modalidade Crédito
Processa pagamentos via cartão de crédito.
Os cartões aceitos na modalidade de crédito são American Express, Visa, MasterCard, Discover, JCB, Diners Club, Aura, Elo.

### Ações disponíveis para pagamento:
* Pedido
  * Registra a transação sem sensibilizar o cartão do cliente.
* Apenas Autorizar
  * Autoriza o desconto no cartão do cliente. Nesta situação o cliente já pode ver o lançamento futuro no extrato. Mas o lojista ainda tem 5 dias para aceitar ou recusar a transação.
* Capturar e Autorizar
  * Autoriza o desconto no cartão do cliente, e captura o valor.

_É possível escolher a forma de autorização (com ou sem autenticação)._

### Opções de parcelamento:
* Crédito à vista
* Parcelado loja
* Parcelado administradora

_É possível configurar o número de parcelas, e valor mínimo da parcela._

## Modalidade Débito
Processa pagamentos via cartão de débito.
Os cartões aceitos na modalidade de crédito são Visa e MasterCard.

Ao escolher a modalidade débito, o cliente é redirecionado ao ambiente do banco emissor do cartão, para autenticação. A autenticação é obrigatória, e pode ser feita via token, sms, ou qualquer outra forma disponibilizada pelo banco emissor do cartão.
