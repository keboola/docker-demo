# Keboola Docker Demo

This is a working example of a Docker working in KBC. Functionality is simple, splits long text columns into multiple rows and adds index number into a new column.

## Install & build

```
git clone https://github.com/keboola/docker-demo.git
cd docker-demo
docker build -t keboola/docker-demo .
```

## Running

```
docker run \
--volume=/Users/ondra/Coding/docker/data:/home/data \
--memory=64m \
--cpu-shares=1 \
--rm \
keboola/docker-demo:latest 
```

Note: `--volume` needs to be adjusted accordingly.

## Sample configuration
Mapped to `/home/data/config.yml` 

```
storage_api:
  token: 123456
input:
  tables:
    0:
      source: in.c-main.data
output:
  tables:
    0:
      destination: out.c-main.data
primary_key_column: id
data_column: text
string_length: 255
```
## Data sample

### Source
Mapped to `/home/data/in/tables/in.c-main.data.csv`

```
id,text,some_other_column
1,""Short text","Whatever"
2,"Long text Long text Long text","Something else"
```

### Destination
Created in `/home/data/out/tables/out.c-main.data.csv`


```
id,text,row_number
1,"Short text",1
2,"Long text Long ",1
2,"text Long Text",2

```

# Environment

Description of the whole environment around Docker images and containers within Keboola Connection.  

## Docker image

Docker image must be able to run as executable, `Dockerfile` must contain `ENTRYPOINT` or `CMD`.


### Configuration

To be defined further, but you will have options to set:

  * format of injected config file (yml, json, ini, ENV variables)
  * container memory limit
  * whether or not the input and output mapping is provided by Keboola Connection


## Workflow

What happens before and after running a Docker container.

  - Download and build specified docker image
  - Download all files/tables specified in input mapping
  - Create configurationi file
  - Run image
  - Upload all files in output mapping
  - Delete image and all temporary files


### Errors

The script defined in `ENTRYPOINT` or `CMD` can provide an exit status. Everything >0 is considered an error and then all content of `STDOUT` will be logged in the error detail.

## Data & configuration injection

Keboola Connection will inject configuration and (optionally) an input mapping in the Docker container in `/home/data` folder. 


### Configuration

The configuration file will be one of the following, depending on the settings.

 - `/home/data/config.yml`
 - `/home/data/config.json`
 - `/home/data/config.ini` 
 
The configuration file will contain all configuration settings (including input and output mapping even if the mapping is provided by Keboola Connection).

Configuration file will contain:

 - `storage_api.token` - Storage API token, that was used to run the Docker image in Keboola Connection
 - `input.tables` (optional) - array of input mappings (see further)
 - `input.files` (optional) - array of file upload queries (see futher)
 - `output.tables` (optional) - array of output mappings
 - `output.files` (optional) - array of files that will be uploaded to Storage API
 - all other configuration options defined for the container


### Input Mapping

As a part of container configuration you can specify tables and files that will be downloaded and provided to the container.

#### Tables

Tables from input mapping will are mounted to `/home/data/in/tables`, where file name equals to the table name with `.csv` suffix. 

Input mapping parameters are similar to [Transfiormation API input mapping ](http://wiki.keboola.com/home/keboola-connection/devel-space/transformations/input-mapping). If `destination` is not set, the CSV file will have the same name as the table.

The tables element in configuration is an array.

##### Examples

Download tables `in.c-ex-salesforce.Leads` and `in.c-ex-salesforce.Accounts` to `/home/data/tables/in/leads.csv` and `/home/data/tables/in/accounts.csv`

```
input:
  tables:
    0:
      source: in.c-ex-salesforce.Leads
      destination: leads
    1:
      source: in.c-ex-salesforce.Accounts
      destination: accounts

```


Download 2 days of data from table `in.c-storage.StoredData` to `/home/data/tables/in/in.c-storage.StoredData.csv`

```
input:
  tables:
    0:
      source: in.c-storage.StoredData
      days: 2  
```

Download only certain columns

```
input:
  tables:
    0:
      source: in.c-ex-salesforce.Leads
      columns: ["Id", "Revenue", "Date", "Status"]
```

Download filtered table

```
input:
  tables:
    0:
      source: in.c-ex-salesforce.Leads
      destination: closed_leads
      whereColumn: Status
      whereValues: ["Closed Won", "Closed Lost"]
      whereOperator: eq
      
```



#### Files

TBD, format for querying files.

### Output Mapping

Output mapping can be defined at multiple places - in configuration file or in manifests, for both tables and files.

Basically manifests allow you to process files in `/home/data/out` folder without defining them in the output mapping. That allows for flexible and dynamic output mapping, where the structure is unknown at the beginning.

#### Tables

In the simplest format, output mapping processes CSV files in the `/home/data/out/tables` folder and uploads them into tables. The name of the file may be equal to the name of the table.

Output mapping parameters are similar to [Transfiormation API output mapping ](http://wiki.keboola.com/home/keboola-connection/devel-space/transformations/intro#TOC-Output-mapping). If `source` is not set, the CSV file is expected to have the same name as the `destination` table.

The tables element in configuration is an array.

##### Examples

Upload `/home/data/out/tables/out.c-main.data.csv` to `out.c-main.data`.

```
output:
  tables:
    0:
      destination: out.c-main.data
```

Upload `/home/data/out/tables/data.csv` to `out.c-main.data`.
with a primary key and incrementally.

```
output:
  tables:
    0:
      source: data
      destination: out.c-main.data
      incremental: 1
      primaryKey: ["id"]
```

Delete data from `destination` table before uploading the CSV file (only makes sense with `incremental: 1`).

```
output:
  tables:
    0:
      destination: out.c-main.Leads
      incremental: 1
      deleteWhereColumn: Status
      deleteWhereValues: ["Closed"]
      deleteWhereOperator: eq              
```

##### Manifests

To allow dynamic data outputs, that cannot be determined before running the container, each file in `/home/data/out` directory can contain a manifest with the output mapping settings in YAML format.

```
/home/data/out/table.csv
/home/data/out/table.csv.manifest
```

`/home/data/out/table.csv.manifest`: 

```
destination: out.c-main.Leads
incremental: 1
deleteWhereColumn: Status
deleteWhereValues: ["Closed"]
deleteWhereOperator: eq    
```

#### Files

TBD.