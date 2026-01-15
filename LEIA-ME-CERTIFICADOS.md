# Sistema de Certificados Avançado - Guia de Uso

O arquivo `certificado.php` foi atualizado para suportar múltiplos modelos de certificados.

## Como Instalar
1. Mantenha o arquivo `certificado.php` no seu plugin de snippets ou pasta de plugins.
2. Certifique-se de que ele esteja ativo.

## Configurando Modelos de Certificado
1. No menu lateral do WordPress, vá em **"Certificados" > "Adicionar Novo Modelo"**.
2. Dê um título para o modelo (ex: "Certificado Padrão 2024", "Certificado Front-End").
3. Na caixa **"Configurações do Layout"**:
    - **Imagem de Fundo**: Faça upload da arte do certificado.
    - **Estilos Globais**: Defina cor e tamanho da fonte.
    - **Posições**: Configure Top/Left (em %) para Nome do Aluno, Nome do Curso e Data.
    - **Visibilidade**: Selecione se deseja exibir o Nome do Curso e a Data. Por padrão, eles vêm desligados, exibindo apenas o Nome do Aluno.
4. Clique em **Publicar**.

## Vinculando ao Curso
1. Vá em **"Cursos"** e edite o curso desejado.
2. Na lateral direita (barra lateral), procure a caixa **"Certificado de Conclusão"**.
3. Selecione qual modelo de certificado este curso deve usar.
4. Clique em **Atualizar** no curso.

## Como Usar no Site
Para exibir seus certificados, crie uma página (ex: "Meus Certificados") e insira o shortcode:

```
[certificado]
```

**Comportamento:**
1. **Sem parâmetros**: Exibe uma grade com todos os cursos que o usuário concluiu (100%), permitindo clicar para ver o certificado.
2. **Com parâmetro**: Se acessar via link direto (ex: `?curso_id=123`), exibe diretamente o certificado do curso especificado.

Exemplo de uso antigo (ainda válido para links diretos):
```html
<a href="/meus-certificados/?curso_id=O_ID_DO_CURSO">Baixar Certificado</a>
```

O sistema automaticamente carregará o modelo selecionado para aquele curso específico. Se nenhum modelo for selecionado no curso, um erro será exibido.

## Notas
- Cada curso pode ter um certificado diferente, com posições e imagens únicas.
- Você pode reutilizar o mesmo modelo para vários cursos.
