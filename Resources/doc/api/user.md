# Oro\Bundle\ZendeskBundle\Entity\User

## ACTIONS  

### get

Retrieve a specific Zendesk user record.

{@inheritdoc}

### get_list

Retrieve a collection of Zendesk user records.

{@inheritdoc}

### create

Create a new Zendesk user record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendeskusers",
      "attributes": {
         "originId": "1000000",
         "name": "Arthur Figueroa",
         "email": "arthur.figueroa_7c48c@msn.com"
      },
      "relationships": {
         "role": {
            "data": {
               "type": "zendeskuserroles",
               "id": "agent"
            }
         },
         "relatedContact": {
            "data": {
               "type": "contacts",
               "id": "1"
            }
         },
         "relatedUser": {
            "data": {
               "type": "users",
               "id": "5"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific Zendesk user record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "zendeskusers",
      "id": "52",
      "attributes": {
         "originId": "1000000",
         "name": "Arthur Figueroa",
         "email": "arthur.figueroa_7c48c@msn.com"
      },
      "relationships": {
         "role": {
            "data": {
               "type": "zendeskuserroles",
               "id": "agent"
            }
         },
         "relatedContact": {
            "data": {
               "type": "contacts",
               "id": "1"
            }
         },
         "relatedUser": {
            "data": {
               "type": "users",
               "id": "5"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific Zendesk user record.

{@inheritdoc}

### delete_list

Delete a collection of Zendesk user records.

{@inheritdoc}

## SUBRESOURCES

### relatedContact

#### get_subresource

Retrieve the record of the Oro contact that is associated with a specific Zendesk user. 

#### get_relationship

Retrieve the ID of the Oro contact that is associated with a specific Zendesk user. 

#### update_relationship

Replace the Oro user that is associated with a specific Zendesk user.   

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "contacts",
    "id": "1"
  }
}
```
{@/request}

### relatedUser

#### get_subresource

Retrieve the record of the Oro user that is associated with a specific Zendesk user. Related user appears when the ticket is submitted to Zendesk by an Oro user.

#### get_relationship

Retrieve the ID of the Oro user that is associated with a specific Zendesk user.

#### update_relationship

Replace the Oro user that is associated with a specific Zendesk user.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```
{@/request}

### role

#### get_subresource

Retrieve the record of the Zendesk role that is assigned to a specific Zendesk user.

#### get_relationship

Retrieve the ID of the Zendesk role that is assigned to a specific Zendesk user.

#### update_relationship

Replace the Zendesk role for a specific Zendesk user.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "zendeskuserroles",
    "id": "agent"
  }
}
```
{@/request}

### channel

#### get_subresource

Retrieve an integration channel via which information about the Zendesk user is received.

#### get_relationship

Retrieve the ID of an integration channel via which information about the Zendesk user is received.

#### update_relationship

Replace an integration channel via which information about the Zendesk user is received.

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
