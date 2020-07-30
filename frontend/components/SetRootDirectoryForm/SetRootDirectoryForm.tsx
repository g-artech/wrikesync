import React from 'react'
import { useForm } from "react-hook-form";


export const SetRootDirectoryForm = ({callback}) => {
    const {register, handleSubmit} = useForm()

    const getFormData = (event) => {
        if (event['root_dir_id'].length > 0) {
            callback('nextcloud.filesystem.root.id', event['root_dir_id'])
        }
    }

    return (
        <div>
            <table style={{width: '100%'}}>
                <tbody>
                    <tr key={'root_dir_form'} style={{display: 'flex', flexDirection: 'row', padding: '5px 10px'}}>
                        <td
                            style={{
                                width: '40%',
                                verticalAlign: 'center',
                                height: '34px'
                            }}
                        >
                            <label>Root Directory ID</label>
                        </td>

                        <td
                            style={{
                                width: '60%',
                                verticalAlign: 'center'
                            }}
                        >
                            <form onSubmit={handleSubmit(getFormData)}>
                                <input
                                    type="text"
                                    name={'root_dir_id'}
                                    ref={register}
                                    style={{
                                        width: '80%',
                                        paddingRight: '5px'
                                    }}
                                />
                                <button
                                    style={{width: '20%'}}
                                    type='submit'
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    )
}