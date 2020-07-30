import React, {useEffect, useState} from 'react'
import axios from '@nextcloud/axios'
import {BulletList} from 'react-content-loader'
// import TableLoader from '../../components/TableLoader/tableLoader'
import {InputAndButton} from '../../components/InputAndButton/InputAndButton'
// import {getSpaces} from '../../services/Api'
// import {useAsync} from '../../services/useAsync'


const baseURL = (window.location.href.indexOf('index.php') > -1) ? '/index.php/apps/wrikesync' : '/apps/wrikesync'

export const SetMappingForm = () => {
    const [spacesFolderData, setSpacesFolderData] = useState(null)
    const [currentUser, setCurrentUser] = useState(null)
    const [mappings, setMappings] = useState(null)


    const getCurrentUser = async () => {
        await axios
            .get(`${baseURL}/config/currentUser`)
            .then(({data}) => {
                // console.log('getMappings: RESPONSE FROM API', data)
                return data
            })
            .then(currentUser => {
                // console.log('GET Mappings - RESPONSE', mappings)
                setCurrentUser(currentUser)
            })
            .catch((err) => {
                console.log('getSpaces: ERROR API', err)
            })
    }

    const getMappings = async () => {
        await axios
            .get(`${baseURL}/mappings`)
            .then(({data}) => {
                // console.log('getMappings: RESPONSE FROM API', data)
                return data
            })
            .then(mappings => {
                // console.log('GET Mappings - RESPONSE', mappings)
                setMappings(mappings)
            })
            .catch((err) => {
                console.log('getSpaces: ERROR API', err)
            })
    }

    const getSpaces = async () => {
        await axios
            .get(`${baseURL}/wrike/spaces`)
            .then(({data}) => {
                // console.log('getSpaces: RESPONSE FROM API', data)
                return data
            })
            .then(spaces => {
                // console.log('GET SPACES - RESPONSE', spaces)
                spaces = spaces.filter(item => item.accessType !== 'Personal')
                return getSpacesFolders(spaces)
            })
            .catch((err) => {
                console.log('getSpaces: ERROR API', err)
            })
    }

    const getFoldersForSpace = async (space) => {
        return await axios
            .get(`${baseURL}/wrike/folders/${space.spaceId}/folders`)
            .then(({data}) => {
                // console.log('getSpacesFolders: RESPONSE FROM API', data)
                const entries = data.map(folder => {
                    let mappingData = mappings.filter(mapping => {
                        return mapping.wr_folder_id === folder.folderId
                    })

                    if (mappingData.length > 0 && mappingData[0].full_path) {
                        mappingData[0].full_path = mappingData[0].full_path.replace(`/${currentUser}/files`, '')
                    }

                    return {
                        ...folder,
                        mapping: {...mappingData[0]}
                    }
                })
                return {
                    spaceId: space.spaceId,
                    title: space.title,
                    entries: entries
                }
            })
            .catch((err) => {
                // console.log('getSpacesFolders: ERROR API', err)
            })
    }

    const getSpacesFolders = async (spaces) => {
        // console.log('getSpacesFolder (spaces): ', spaces)
        return await Promise.all(
            spaces.map(async space => {
                return await getFoldersForSpace(space)
            })
        )
            .then(folders => {
                setSpacesFolderData(folders)
                // console.log('FINAL FOLDERS: ', folders)
            })
    }

    const handleSetMapping = async (folderId, value) => {
        await axios
            .post(`${baseURL}/mappings/forName`, JSON.stringify({
                    ncNodeName: value,
                    wrFolderId: folderId
                }),
                {
                    headers: {
                        'Content-Type': 'application/json',
                    }
                }
            )
            .then(() => {
                // console.log('MAPPING SUCCESSFULLY ADDED (Response): ', data)
                setSpacesFolderData(null)
                setMappings(null)
            })
            .then(() => {
                getMappings()
            })
            .catch(err => {
                setSpacesFolderData(null)
                setMappings(null)
            })
            .then(() => {
                getMappings()
            })
        /*.catch((err) => {
            // console.log(`ERROR WHILE DELETING MAPPING WITH ID ${mappingId}: `, err)
        })*/
    }

    const handleDeleteMappingForId = async (mappingId) => {
        // console.log('mappingId: ', mappingId)
        await axios
            .delete(`${baseURL}/mappings/${mappingId}`)
            .then(({data}) => {
                // console.log('MAPPING SUCCESSFULLY DELETED (Response): ', data)
                setSpacesFolderData(null)
                setMappings(null)
            })
            .then(() => {
                getMappings()
            })
            .catch((err) => {
                console.log(`ERROR WHILE DELETING MAPPING WITH ID ${mappingId}: `, err)
            })
    }

    /*const refetchDataFromApi = () =>  {
        getMappings()
        // getSpaces()
    }*/

    useEffect(() => {
        getCurrentUser()
    }, [])

    useEffect(() => {
        getMappings()
    }, [currentUser])

    useEffect(() => {
        if (mappings) {
            getSpaces()
        }
    }, [mappings])

    return (
        <div>
            {
                (spacesFolderData && mappings) ?
                    spacesFolderData.map(spaceFoldersArray => {
                        return (
                            <div
                                key={spaceFoldersArray.spaceId}
                                style={{
                                    padding: '5px'
                                }}
                            >

                                <hr/>

                                <h3
                                    style={{
                                        fontWeight: 'bold',
                                        padding: '0 10px'
                                    }}
                                >
                                    {spaceFoldersArray.title}
                                </h3>
                                <table style={{width: '100%'}}>
                                    <tbody>
                                    {spaceFoldersArray.entries.map(folder => {
                                        return (
                                            <tr key={folder.folderId} style={{display: 'flex', flexDirection: 'row', padding: '5px 10px'}}>
                                                <td
                                                    style={{
                                                        width: '40%',
                                                        verticalAlign: 'center',
                                                        height: '34px'
                                                    }}
                                                >
                                                    <label>{folder.title}</label>
                                                </td>

                                                <td
                                                    style={{
                                                        width: '60%',
                                                        verticalAlign: 'center'
                                                    }}
                                                >
                                                    {/*<form onSubmit={handleSubmit(handleSetMapping)}>*/}
                                                    {
                                                        !folder.mapping.id ?
                                                            <>
                                                                <InputAndButton folder={folder} callback={handleSetMapping} />
                                                            </>
                                                            :
                                                            <>
                                                                <input
                                                                    type="text"
                                                                    name={folder.folderId}
                                                                    disabled
                                                                    defaultValue={folder.mapping.full_path}
                                                                    style={{
                                                                        width: '80%',
                                                                        paddingRight: '5px'
                                                                    }}
                                                                />
                                                                <button
                                                                    style={{width: '20%'}}
                                                                    onClick={() => handleDeleteMappingForId(folder.mapping.id)}
                                                                >
                                                                    Delete
                                                                </button>
                                                            </>
                                                    }
                                                    {/*</form>*/}
                                                </td>
                                            </tr>
                                        )
                                    })}
                                    </tbody>
                                </table>
                            </div>
                        )
                    })
                    :
                    <>
                        <h2>Fetching Data from Wrike-API ...</h2>
                        <BulletList />
                        {/*<BulletList />
						<BulletList />*/}
                    </>
            }
        </div>
    )
}