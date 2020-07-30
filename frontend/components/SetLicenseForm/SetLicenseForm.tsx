import React from 'react'
import { useForm } from "react-hook-form";


export const SetLicenseForm = ({callback}) => {
    const {register, handleSubmit} = useForm()

    const getFormData = (event) => {
        console.log('license FormData: ', event)
        if (event['license_key'].length > 0 && event['license_password'].length > 0) {
            callback(event['license_key'], event['license_password'])
        }
    }

    return (
        <div>
            <form onSubmit={handleSubmit(getFormData)}>
                <table style={{width: '100%'}}>
                    <tbody>
                        <tr key={'license_row'} style={{display: 'flex', flexDirection: 'row', padding: '5px 10px'}}>
                            <td
                                style={{
                                    width: '40%',
                                    verticalAlign: 'center',
                                    height: '34px'
                                }}
                            >
                                <label>License</label>
                            </td>

                            <td
                                style={{
                                    width: '60%',
                                    verticalAlign: 'center'
                                }}
                            >
                                <input
                                    type="text"
                                    name={'license_key'}
                                    ref={register}
                                    style={{
                                        width: '100%',
                                        paddingRight: '5px'
                                    }}
                                />
                            </td>
                        </tr>
                        <tr key={'password_row'} style={{display: 'flex', flexDirection: 'row', padding: '5px 10px'}}>
                            <td
                                style={{
                                    width: '40%',
                                    verticalAlign: 'center',
                                    height: '34px'
                                }}
                            >
                                <label>Password</label>
                            </td>

                            <td
                                style={{
                                    width: '60%',
                                    verticalAlign: 'center'
                                }}
                            >
                                <input
                                    type="text"
                                    name={'license_password'}
                                    ref={register}
                                    style={{
                                        width: '100%',
                                        paddingRight: '5px'
                                    }}
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style={{
                    textAlign: 'right'
                }}>
                    <button
                        style={{width: '12%'}}
                        type='submit'
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    )
}