# PagBank Split para Magento e Adobe

Módulo para split de pagamento do PagBank em Magento e Adobe Commerce.

## Recursos

Este módulo é uma extensão do módulo de pagamento do PagBank, projetado para oferecer a funcionalidade de configuração de Split de pagamento.

Como opção de configuração, você pode escolher diferentes cenários para definir os recebedores secundários do Split. 

Aqui estão os dois métodos de configuração disponíveis:

### Configuração pelo módulo

Essa é uma solução ideal para um modelo de negócio em que todas as vendas enviem uma comissão a recebedores secundários.

Nesse fluxo, você pode definir diretamente os dados dos recebedores secundários na configuração do módulo. Essa opção permite:

- Enviar uma porcentagem de comissão (valor decimal de 1 a 100) para todos os recebedores cadastrados.
- Repassar o valor dos juros e o valor do frete.

Na distribuição dos juros e do frete com base, será respeitada a proporção do número de itens vendidos em relação ao número de vendedores. Ou seja, para uma venda com 3 itens e 3 vendedores, cada vendedor receberá 1/3 de cada valor.

#### Modelos de negócios indicados para essa solução

- Agências/Parceiros que recebem comissão por vendas.
- Sites MultiStores em que cada view tem seu próprio vendedor e que todos pagam uma comissão ao site principal.


### Configuração por outros módulos

Essa é uma solução ideal para um modelo de negócio de Marketplace, em que a cada venda pode haver n vendedores.

Nesse processo, as informações de valores e vendedores são obtidas por outros recursos. Por exemplo, os clientes que utilizam o Webkul Marketplace podem interagir com o módulo e passar os valores para o Split de pagamento.

#### Modelos de negócios indicados para essa solução

- Soluções de Marketplace implementadas por módulos de terceiros
- Solução de Split com regras avançadas de comissionamento


## License

[Open Source License](../../LICENSE)
