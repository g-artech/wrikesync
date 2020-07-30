import React, {useEffect, useState} from 'react'
import axios from '@nextcloud/axios'

import {SetMappingForm} from '../SetMappingForm/SetMappingForm'
import {SetLicenseForm} from '../../components/SetLicenseForm/SetLicenseForm'
import {SetRootDirectoryForm} from '../../components/SetRootDirectoryForm/SetRootDirectoryForm'


const baseURL = (window.location.href.indexOf('index.php') > -1) ? '/index.php/apps/wrikesync' : '/apps/wrikesync'

export default () => {
    const [licenseStatus, setLicenseStatus] = useState(false)
    const [licenseStatusRequest, setLicenseStatusRequest] = useState(false)
    const [rootDirectory, setRootDirectory] = useState(null)

    const checkConfigStatus = (configData) => {
        // console.log('configData', configData)
        if (configData.length > 0) {
            const licenseStatus = getLicenseStatus(configData)

            const rootDirectory = configData.filter(item => {
                return item.key === 'nextcloud.filesystem.root.id'
            })
            // console.log('checkConfig - rootDirectory: ', rootDirectory)
            setLicenseStatus(licenseStatus)
            if (rootDirectory.length > 0) {
                setRootDirectory({
                    key: rootDirectory[0].key,
                    value: rootDirectory[0].value
                })
            }
        }
    }

    const checkLicenseConfig = (configData) => {
        // console.log('configData', configData)
        if (configData.length > 0) {
            const resultData = configData.filter(item => {
                return (
                    item.key === 'license.key' ||
                    item.key === 'license.encryption.password' ||
                    item.key === 'wrike.api.host' ||
                    item.key === 'wrike.api.port' ||
                    item.key === 'wrike.api.protocol' ||
                    item.key === 'wrike.api.path' ||
                    item.key === 'nextcloud.base.url' ||
                    item.key === 'wrike.api.auth.token'
                )
            })
            // console.log('checkLicenseConfig (resultData.length === 8):', (resultData.length === 8))
            setLicenseStatusRequest((resultData.length === 8))
            // setLicenseStatus(resultData.length === 8)
        }
    }

    const getLicenseStatus = (configData) => {
        // console.log('configData', configData)
        if (configData.length > 0) {
            const resultData = configData.filter(item => {
                return (
                    item.key === 'license.key' ||
                    item.key === 'license.encryption.password' ||
                    item.key === 'wrike.api.host' ||
                    item.key === 'wrike.api.port' ||
                    item.key === 'wrike.api.protocol' ||
                    item.key === 'wrike.api.path' ||
                    item.key === 'nextcloud.base.url' ||
                    item.key === 'wrike.api.auth.token'
                )
            })
            return (resultData.length === 8)
            // setLicenseStatus(resultData.length === 8)
        }
    }

    const getConfigDataForForm = async () => {
        await axios
            .get(`${baseURL}/config`)
            .then(({data}) => {
                // console.log('getConfigData: RESPONSE FROM API', data)
                return data
            })
            .then(config => {
                // console.log('GET Mappings - RESPONSE', mappings)
                checkConfigStatus(config)
            })
            .catch((err) => {
                console.log('getConfigDataForForm: ERROR API', err)
            })
    }

    const getConfigData = async () => {
        return await axios
            .get(`${baseURL}/config`)
            .then(({data}) => {
                console.log('getConfigData: RESPONSE FROM API', data)
                return data
            })
            .catch((err) => {
                console.log('getConfigData: ERROR API', err)
            })
    }

    const setLicenseData = async (license, password) => {
        // console.log('setLicenseData (license, password)', license, password)
        let licenseDataObject = {
            license_key: null,
            license_password: null
        }
        const actualConfigData = await getConfigData()

        // console.log('actualConfigData', actualConfigData)
        actualConfigData.forEach(item => {
            if (item.key === 'license.key') {
                licenseDataObject['license_key'] = {...item}
            }

            if (item.key === 'license.encryption.password') {
                licenseDataObject['license_password'] = {...item}
            }
        })

        if (licenseDataObject.license_key) {
            // console.log('license_key found (licenseDataObject): ', licenseDataObject)
            await deleteLicenseConfigData(licenseDataObject.license_key.id)
        }

        if (licenseDataObject.license_password) {
            // console.log('license_password found (licenseDataObject): ', licenseDataObject)
            await deleteLicenseConfigData(licenseDataObject.license_password.id)
        }

        await setLicenseConfigData('license.key', license)
        await setLicenseConfigData('license.encryption.password', password)

        // setTimeout(async () => {
            await axios
                .get(`${baseURL}/nextcloud/license`)
                .then(({data}) => {
                    // console.log('setLicenseData -> getConfigData: RESPONSE FROM API', data)
                    return data
                })
                .then(() => {
                    return getConfigData()
                })
                .then(config => {
                    checkLicenseConfig(config)
                })
                .catch((err) => {
                    console.log('setLicenseData -> getConfigData: ERROR API', err)
                })
        // }, 1500);
    }

    const setConfigData = async (key, value) => {
        // console.log('setConfigData (key, value): ', key, value)
        await axios
            .post(`${baseURL}/config`,JSON.stringify({
            key,
            value
        }),
            {
                headers: {
                    'Content-Type': 'application/json',
                }
            }
            )
            .then(({data}) => {
                // console.log('setConfigData: RESPONSE FROM API', data)
                // return data
            })
            .then(() => {
                getConfigDataForForm()
            })
            .catch((err) => {
                console.log('setConfigData: ERROR API', err)
            })
    }

    const setLicenseConfigData = async (key, value) => {
        // console.log('setLicenseConfigData (callback License) (key, value): ', key, value)
        await axios
            .post(`${baseURL}/config`,JSON.stringify({
            key,
            value
        }),
            {
                headers: {
                    'Content-Type': 'application/json',
                }
            }
            )
            .then(({data}) => {
                console.log('setLicenseConfigData: RESPONSE FROM API', data)
                // return data
            })
            .catch((err) => {
                console.log('setLicenseConfigData: ERROR API', err)
            })
    }

    const deleteLicenseConfigData = async (id) => {
        // console.log('deleteLicenseConfigData (callback License) (id, value): ', id)
        await axios
            .delete(`${baseURL}/config/${id}`)
            .then(({data}) => {
                // console.log('setLicenseConfigData: RESPONSE FROM API', data)
                // return data
            })
            .catch((err) => {
                console.log('setLicenseConfigData: ERROR API', err)
            })
    }

    useEffect(() => {
        getConfigDataForForm()
    }, [])

    useEffect(() => {
        // 'useEffect (licenseStatusRequest): ', licenseStatusRequest)
        if (licenseStatusRequest === true) {
            getConfigDataForForm()
        }
    }, [licenseStatusRequest])

	return (
        <div
            style={{
                width: '100%',
                display: 'flex',
                flexDirection: 'column'
            }}
        >
            <h2>WrikeSync Settings</h2>
            {
                !licenseStatus ?
                    <>
                        <h3>Set License Key</h3>
                        <SetLicenseForm callback={setLicenseData} />
                    </>
                    : null
            }

            {
                (licenseStatus && !rootDirectory) ?
                    <>
                        <h3>Set Nextcloud Root Directory</h3>
                        <SetRootDirectoryForm callback={setConfigData} />
                    </>
                    : null
            }

            {
                (licenseStatus && rootDirectory) ?
                    <>
                        <h3>Space-Folder Mapping</h3>
                        <SetMappingForm />
                    </>
                    : null
            }
        </div>
    )
}
