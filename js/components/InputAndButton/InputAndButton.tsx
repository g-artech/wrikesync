import React from 'react'
import { useForm } from "react-hook-form";


export const InputAndButton = ({folder, callback}) => {
    const {register, handleSubmit} = useForm()

    const getFormData = (event) => {
        if (event[folder.folderId].length > 0) {
            callback(folder.folderId, event[folder.folderId])
        }
    }

    return (
        <form onSubmit={handleSubmit(getFormData)}>
            <input
                type="text"
                name={folder.folderId}
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
    )
}