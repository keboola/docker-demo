# Keboola Docker Demo

This is a working example of a Docker working in KBC. Functionality is simple, splits long text columns into multiple rows and adds index number into a new column.

## Install & build

```
git clone https://github.com/keboola/docker-demo.git
cd docker-demo
sudo docker build -t keboola/docker-demo .
```

## Runing a container

```
sudo docker run \
--volume=/home/ec2-user/data:/data \
--memory=64m \
--cpu-shares=1 \
--rm \
keboola/docker-demo:latest 
```

Note: `--volume` needs to be adjusted accordingly.

## Sample configuration
Mapped to `/data/config.yml` 

```
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
Mapped to `/data/in/tables/in.c-main.data.csv`

```
id,text,some_other_column
1,""Short text","Whatever"
2,"Long text Long text Long text","Something else"
```

### Destination
Created in `/data/out/tables/out.c-main.data.csv`


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

  * format of injected config file and manifests (YAML, JSON, INI)
  * container memory limit
  * whether or not the input and output mapping is provided by Keboola Connection


## Workflow

What happens before and after running a Docker container.

  - Download and build specified docker image
  - Download all tables and files specified in input mapping
  - Create configurationi file
  - Run the container
  - Upload all tables and files in output mapping
  - Delete the container and all temporary files


### Errors

The script defined in `ENTRYPOINT` or `CMD` can provide an exit status. Everything >0 is considered an error and then all content of `STDOUT` will be logged in the error detail.

## Data & configuration injection

Keboola Connection will inject configuration and (optionally) an input mapping in the Docker container in `/data` folder. 


### Configuration

The configuration file will be one of the following, depending on the settings.

 - `/data/config.yml`
 - `/data/config.json`
 - `/data/config.ini` 
 
The configuration file will contain all configuration settings (including input and output mapping even if the mapping is provided by Keboola Connection).

Configuration file will contain:

 - `input.tables` (optional) - array of input mappings (see further)
 - `input.files` (optional) - array of file upload queries (see futher)
 - `output.tables` (optional) - array of output mappings
 - `output.files` (optional) - array of files that will be uploaded to Storage API
 - all other configuration options defined for the container
 - `storage_api.token` (optional) - if image has granted access to the token, it will be provided to the container


### Input Mapping

As a part of container configuration you can specify tables and files that will be downloaded and provided to the container.

#### Tables

Tables from input mapping will are mounted to `/data/in/tables`, where file name equals to the table name with `.csv` suffix. 

Input mapping parameters are similar to [Transfiormation API input mapping ](http://wiki.keboola.com/home/keboola-connection/devel-space/transformations/input-mapping). If `destination` is not set, the CSV file will have the same name as the table.

The tables element in configuration is an array.

##### Examples

Download tables `in.c-ex-salesforce.Leads` and `in.c-ex-salesforce.Accounts` to `/data/tables/in/leads.csv` and `/data/tables/in/accounts.csv`

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


Download 2 days of data from table `in.c-storage.StoredData` to `/data/tables/in/in.c-storage.StoredData.csv`

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

You can also download files from file uploads using a ES query.

```
input:
  files:
    0:
      query: tags:"keboola/docker-demo"
```

All files that will match the fulltext search will be downloaded to the `/data/in/files` folder. Each file will also contain a manifest with all information about the file in the chosen format.

```
/data/in/files/75807542
/data/in/files/75807542.manifest
/data/in/files/75807657
/data/in/files/75807657.manifest		
```

`/data/in/files/75807542.manifest`:

```
  id: 75807657
  created: "2015-01-14T00:47:00+0100"
  isPublic: false
  isSliced: false
  isEncrypted: false
  name: "one_2015_01_05allkeys.json.zip"
  sizeBytes: 563416
  tags: 
    - "keboola/docker-demo"
  maxAgeDays: 180
  creatorToken: 
    id: 3800
    description: "ondrej.hlavacek@keboola.com"
```

##### Incremental Processing

Since you might be processing the same files over and over, if the image is set to work incrementally with files from file upload, upon each successful run of the container all files, that have been downloaded, will get tagged with `[IMAGE_ID]:processed` tag (eg. `keboola/docker-demo:processed`). These files will be automatically excluded from the next input mapping.

### Output Mapping

Output mapping can be defined at multiple places - in configuration file or in manifests, for both tables and files.

Basically manifests allow you to process files in `/data/out` folder without defining them in the output mapping. That allows for flexible and dynamic output mapping, where the structure is unknown at the beginning.

#### Tables

In the simplest format, output mapping processes CSV files in the `/data/out/tables` folder and uploads them into tables. The name of the file may be equal to the name of the table.

Output mapping parameters are similar to [Transfiormation API output mapping ](http://wiki.keboola.com/home/keboola-connection/devel-space/transformations/intro#TOC-Output-mapping). If `source` is not set, the CSV file is expected to have the same name as the `destination` table.

The tables element in configuration is an array. 

##### Examples

Upload `/data/out/tables/out.c-main.data.csv` to `out.c-main.data`.

```
output:
  tables:
    0:
      destination: out.c-main.data
```

Upload `/data/out/tables/data.csv` to `out.c-main.data`.
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

To allow dynamic data outputs, that cannot be determined before running the container, each file in `/data/out` directory can contain a manifest with the output mapping settings in the chosen format.

```
/data/out/tables/table.csv
/data/out/tables/table.csv.manifest
```

`/data/out/table.csv.manifest`: 

```
destination: out.c-main.Leads
incremental: 1
```

#### Files

Output files from `/data/out/files` folder are automatically uploaded to file uploads. If the manifest file is defined, the information from the manifest file will be used. 

```
/data/out/files/image.jpg
/data/out/files/image.jpg.manifest
```

These manifest parameters can be used (taken from [Storage API File Import](http://docs.keboola.apiary.io/#files)):

 - name (if not set, will use the filename)
 - contentType 
 - isPublic
 - isPermanent
 - notify
 - tags
 - isEncrypted

#####Example

`/data/out/files/image.jpg.manifest`: 

```
name: image.jpg
contentType: image/jpeg
isPublic: true
isPermanent: true
tags: 
  - image
  - pie-chart
```