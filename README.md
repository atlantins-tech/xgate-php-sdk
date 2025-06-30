# XGATE PHP SDK

Um SDK PHP moderno e robusto para integração com a API da XGATE Global, uma plataforma de pagamentos que oferece soluções para depósitos, saques e conversões entre moedas fiduciárias e criptomoedas.

## Overview

O XGATE PHP SDK simplifica a integração com os serviços da XGATE, fornecendo uma interface intuitiva, bem documentada e seguindo as melhores práticas de desenvolvimento PHP. Este SDK oferece uma abstração limpa sobre os endpoints da XGATE, tratamento robusto de erros, validação de dados e suporte completo às funcionalidades da plataforma.

**Características principais:**
- ✅ Autenticação JWT automática com renovação de tokens
- ✅ Validação rigorosa de dados de entrada
- ✅ Tratamento robusto de erros com exceções específicas
- ✅ Suporte completo a PHPDoc para melhor experiência de desenvolvimento
- ✅ Compatível com PHP 8.1+
- ✅ Segue os padrões PSR-4 e PSR-12

## Funcionalidades da API

### ✅ Autenticação
- [x] **Login/Token de acesso** - Autenticação JWT via email/senha

### 🔄 Gestão de Clientes
- [ ] **Criar cliente** - Registro de novos clientes na plataforma
- [ ] **Buscar cliente** - Consulta de informações de clientes existentes
- [ ] **Validação de dados** - Validação de CPF, email e outros dados obrigatórios

### 🔄 Sistema PIX
- [ ] **Criar chave PIX** - Criação de chaves PIX para clientes
- [ ] **Listar chaves PIX** - Consulta de chaves PIX por cliente
- [ ] **Validação de chaves** - Suporte a CPF, CNPJ, email, telefone e chave aleatória

### 🔄 Operações FIAT (Moeda Fiduciária)
- [ ] **Listar moedas para depósito** - Consulta de moedas fiduciárias disponíveis
- [ ] **Criar depósito** - Criação de ordens de depósito
- [ ] **Listar moedas para saque** - Consulta de moedas disponíveis para saque
- [ ] **Criar saque** - Processamento de saques via PIX

### 🔄 Operações Cripto
- [ ] **Consultar carteira cripto** - Busca de carteiras de criptomoedas dos clientes
- [ ] **Listar redes blockchain** - Consulta de redes blockchain disponíveis
- [ ] **Criar saque cripto** - Processamento de saques para carteiras externas

### 🔄 Sistema de Conversões
- [ ] **Verificar conversão** - Consulta de taxas de conversão entre moedas

### Legenda
- ✅ **Implementado** - Funcionalidade completa e testada
- 🔄 **Em desenvolvimento** - Funcionalidade em progresso
- ⏳ **Planejado** - Funcionalidade planejada para próximas versões

## Gerenciamento de Tarefas

Este projeto utiliza o [Task Master](https://github.com/Starlord-Technologies/task-master-ai) para gerenciar o desenvolvimento e as tarefas. O Task Master é uma ferramenta de gerenciamento de tarefas orientada por IA que ajuda a organizar, priorizar e acompanhar o progresso do desenvolvimento.

### Como acompanhar o progresso:
- As tarefas detalhadas estão disponíveis no diretório `.taskmaster/tasks/`
- Use `task-master list` para ver todas as tarefas
- Use `task-master next` para ver a próxima tarefa a ser trabalhada
- Use `task-master show <id>` para ver detalhes de uma tarefa específica

## Instalação

> **Nota**: Este SDK ainda está em desenvolvimento. A instalação via Composer estará disponível quando a primeira versão estável for lançada.

```bash
# Quando disponível:
composer require xgate/php-sdk
```

## Configuração

> **Nota**: Documentação de configuração e exemplos de uso serão adicionados conforme o desenvolvimento progride.

## Contribuindo

Contribuições são bem-vindas! Este projeto segue as melhores práticas de desenvolvimento PHP e utiliza o Task Master para organização das tarefas.

### Processo de contribuição:
1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Suporte

Para dúvidas, problemas ou sugestões, abra uma [issue](https://github.com/seu-usuario/xgate-php-sdk/issues) no GitHub.

---

**Status do Projeto**: 🚧 Em Desenvolvimento Ativo

Desenvolvido com ❤️ para a comunidade PHP brasileira. 