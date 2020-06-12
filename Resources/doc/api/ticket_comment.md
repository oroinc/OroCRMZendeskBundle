# Oro\Bundle\ZendeskBundle\Entity\TicketComment

## ACTIONS  

### get

Retrieve a specific Zendesk ticket comment record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk ticket comment records.

{@inheritdoc}

### create

Create a new Zendesk ticket comment record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendeskticketcomments",
      "attributes": {
         "originId": "1000054",
         "body": "Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.",
         "htmlBody": "Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.",
         "public": true
      },
      "relationships": {
         "author": {
            "data": {
               "type": "zendeskusers",
               "id": "39"
            }
         },
         "ticket": {
            "data": {
               "type": "zendesktickets",
               "id": "10"
            }
         },
         "relatedComment": {
            "data": {
               "type": "casecomments",
               "id": "82"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific Zendesk ticket comment record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendeskticketcomments",
      "id": "55",
      "attributes": {
         "originId": "1000054",
         "body": "Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.",
         "htmlBody": "Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.",
         "public": true
      },
      "relationships": {
         "author": {
            "data": {
               "type": "zendeskusers",
               "id": "39"
            }
         },
         "ticket": {
            "data": {
               "type": "zendesktickets",
               "id": "10"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific Zendesk ticket comment record.

{@inheritdoc}

### delete_list

Delete a collection of Zendesk ticket comment records.

{@inheritdoc}

## SUBRESOURCES

### author

#### get_subresource

Retrieve the Zendesk user who authored the comment.

#### get_relationship

Retrieve the ID of the Zendesk user who authored the comment.

#### update_relationship

Replace the Zendesk user who authored the comment.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskusers",
    "id": "3"
  }
}
```
{@/request}

### relatedComment

#### get_subresource

Retrieve the Oro case comment that is associated with a specific Zendesk ticket comment. 

#### get_relationship

Retrieve the ID of the Oro case comment that is associated with a specific Zendesk ticket comment. 

#### update_relationship

Replace the Oro case comment that is associated with a specific Zendesk ticket comment.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "casecomments",
    "id": "2"
  }
}
```
{@/request}

### ticket

#### get_subresource

Retrieve the Zendesk ticket that a specific Zendesk ticket comment was made on.

#### get_relationship

Retrieve the ID of the Zendesk ticket that a specific Zendesk ticket comment was made on.

#### update_relationship

Replace the Zendesk ticket that a specific Zendesk ticket comment was made on.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendesktickets",
    "id": "1"
  }
}
```
{@/request}

### channel

#### get_subresource

Retrieve an integration channel via which information about the Zendesk ticket comment is received.

#### get_relationship

Retrieve the ID of an integration channel via which information about the Zendesk ticket comment is received.

#### update_relationship

Replace an integration channel via which information about the Zendesk ticket comment is received.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "integrationchannels",
    "id": "1"
  }
}
```
{@/request}
