# Quack_Cielo
Integração de pagamento Cielo para Magento 1.x

Esta extensão utiliza a biblioteca [Tritoq/Payment](https://github.com/nezkal/Cielo), e distribui os arquivos da biblioteca, devido a incompatibilidade do Magento 1.x com o uso de namespaces. Agradecimentos ao Artur, que disponibilizou a biblioteca.

## Veja também outras integrações
 - [Itaú Shopline](https://github.com/rafaelpatro/Quack_Itau)
 - [Banco do Brasil](https://github.com/rafaelpatro/Quack_BB)
 - [Bradesco](https://github.com/rafaelpatro/Quack_Bradesco)
 
## Compatibilidade
Magento 1.7, 1.8 e 1.9.x

## Requisitos
* Credenciamento junto ao serviço Cielo E-commerce (deve ser mencionada a escolha por leitura do cartão na própria loja).
* Baixar o certificado SSL do [Webservice Cielo](https://ecommerce.cielo.com.br). Basta acessar a página, clicar sobre o cadeado, e exportar o certificado para o formato _.crt_.

## Benefícios
* Receber pagamentos de forma automática, possibilitando análise de crédito ou captura imediata.
* Livre de intermediários, ou gateways de pagamentos.
* Extensão completamente gratuita, sem propagandas, ou versões pagas.

## Introdução
Neste módulo foram adotados os padrões de integração de cartões de crédito do Magento, visando maior controle e transparência das transações. Ou seja as operações do Magento para Faturar (Invoice), Reembolsar (Refund), etc, estão todas integradas as operações da Cielo.

* Para capturar uma transação basta faturar o pedido.
* Para cancelar uma transação basta cancelar a fatura do pedido.
* Para devolver ao cliente parte ou todo o valor pago, basta reembolsar o pedido.
* O Magento disponibiliza estas e outras opções de acordo com a situação do pedido: rejeitar, aceitar, autorizar, etc.
* Importante: As operações precisam ser marcadas como "online". O Magento permite operações online e offline.

## Segurança
Os detalhes de cada transação ficam mais protegidos em painéis separados, onde é possível gerenciar o acesso.

### A tela do pedido exibe as informações básicas da transação:
![image](https://cloud.githubusercontent.com/assets/13813964/21666264/ba7a6fa0-d2d7-11e6-8c19-209fa98806d8.png)

### Os detalhes da transação ficam registrados na aba _Transações_:
![image](https://cloud.githubusercontent.com/assets/13813964/21666169/3967a6ee-d2d7-11e6-8062-d21a006241aa.png)

### Ao clicar sobre a transação é possível ver os detalhes:
![image](https://cloud.githubusercontent.com/assets/13813964/21666567/9f3b8d9e-d2d9-11e6-8ea5-743528386b62.png)

_Também é possível realizar uma venda pelo backend, mas somente para os casos onde não é requerida autenticação junto ao banco emissor do cartão._

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

## Dicas

### Como exibir imagens das bandeiras dos cartões na finalização do pedido?

Por padrão as opções de seleção do cartão são as mesmas do Magento.
![image](https://cloud.githubusercontent.com/assets/13813964/21870040/a353967a-d841-11e6-8368-e395a5e30bf7.png)

Mas você pode ajustar a exibição facilmente, apenas acessando o Backend. Seguindo os passos abaixo, as bandeiras dos cartões devem aparecer dessa forma:

  ![image](https://cloud.githubusercontent.com/assets/13813964/21869209/9fbd633c-d83d-11e6-81d8-d058475b7a1d.png)

* Liberar a variável _web/secure/base_media_url_ em _Sistema > Permissões > Variáveis_.

* Criar um bloco estático em _CMS > Blocos Estáticos_ e inserir o script abaixo.

```html
    <style type="text/css">
        select[name="payment[cc_type]"] {
            border: none;
            outline: none;
            overflow: visible;
        }
        select[name="payment[cc_type]"] > option {
            background-position: 2px 2px !important;
            background-repeat: no-repeat !important;
            background-size: 58px auto !important;
            display: inline;
            float: left;
            font-size: 0;
            height: 41px;
            margin: 0 5px 5px 0;
            width: 62px;
        }
    </style>
    <script type="text/javascript">
        //<![CDATA[
        payment.addAfterInitFunction('ccFlagStyle', function() {
            $$('select[name="payment[cc_type]"]').each(function(e) {
                e.size = 2;
                if (e.down().value == '') {
                    e.down().remove();
                }
            });
            
            $$('select[name="payment[cc_type]"] > option').each(function(e) {
                var flagName = e.value;
                if (flagName) {
                    e.style.backgroundImage = 'url({{config path="web/secure/base_media_url"}}wysiwyg/' + flagName + '.png)';
                }
            });
        });
        //]]>
    </script>
```

* Ainda na tela do bloco, clicar em _Insert Image..._ e fazer upload das imagens a seguir, com os respectivos nomes:

  * AE.png, MC.png, VI.png, AU.png, JCB.png, DICL.png, DI.png, EL.png
  
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869581/689f9f12-d83f-11e6-8769-ed81ab5a304a.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869596/7bb6e2ea-d83f-11e6-9ace-ca9599c4d49c.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869598/7e82a950-d83f-11e6-8588-2d12e186d533.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869602/8158a2f6-d83f-11e6-9ec2-5a520117b332.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869605/84a69814-d83f-11e6-95b8-6620c1dd182d.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869606/87510a22-d83f-11e6-86c6-2720cc793b81.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869608/89ef3b1e-d83f-11e6-88aa-034d7046cdf7.png)
  ![image](https://cloud.githubusercontent.com/assets/13813964/21869610/8c977afc-d83f-11e6-90aa-73c88100fd89.png)
  
* Criar um Widget para o bloco acima, em _CMS > Widgets_. E adicionar opção de atualizar o layout da _Página Finalizar Pedido_, como ilustrado a seguir:

  ![image](https://cloud.githubusercontent.com/assets/13813964/21869312/2ae6d42a-d83e-11e6-899d-a40927261010.png)
